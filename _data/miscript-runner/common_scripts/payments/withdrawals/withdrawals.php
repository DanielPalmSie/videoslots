<?php

/**
 * Loops through a list of withdrawals to update them and update the status and reference_id in mts.
 *
 * @param string $sc_id         Story Id used in logging and auditing
 * @param string $full_csv_path The CSV file name (including full path) to be processed
 *                              CSV Columns: user_id,withdrawal_id,ext_id,new_status,refund
 */
function updateWithdrawals($sc_id, $full_csv_path)
{
    echo "Updating Withdrawal Details\n";
    echo "-------------\n";

    $system_user = cu("system");

    $withdrawal_to_updates = readCsv($full_csv_path);
    foreach ($withdrawal_to_updates as $withdrawal_to_update) {

        // Read csv variables
        $row_user_id = $withdrawal_to_update['user_id'];
        $row_withdrawal_id = $withdrawal_to_update['withdrawal_id'];
        $row_ext_id = $withdrawal_to_update['ext_id'];
        $row_new_status = (string)$withdrawal_to_update['new_status'];
        $row_refund = $withdrawal_to_update['refund'];
        echo "user_id:{$row_user_id}  withdrawal_id:{$row_withdrawal_id} ext_id:{$row_ext_id} new_status:{$row_new_status}  refund:{$row_refund}\n";
        $user_obj = cu($row_user_id);
        if (empty($user_obj)) {
            echo "  WARNING: User not found - skipping entry!\n";
            continue;
        };

        // Query and check Pending Withdrawal entry
        $pending_withdrawal_entry = phive("SQL")->sh($row_user_id)->loadAssoc("
            SELECT *
            FROM pending_withdrawals
            WHERE user_id = {$row_user_id}
              AND id = '{$row_withdrawal_id}'
        ");
        if (empty($pending_withdrawal_entry)) {
            echo "  WARNING: No withdrawal record found for user_id: {$row_user_id} and withdrawal_id = {$row_withdrawal_id} - skipping entry!\n";
            continue;
        }
        if ((string)$pending_withdrawal_entry['status'] == (string)$row_new_status) {
            echo "  WARNING: Pending withdrawal entry is already set with status {$row_new_status} - skipping entry!\n";
            continue;
        }
        if ((string)$pending_withdrawal_entry['status'] == "disapproved") {
            echo "  WARNING: Pending withdrawal entry is already disapproved - skipping entry!\n";
            continue;
        }

        // Try to fetch related MTS entry
        $mts_entry = phive('SQL')->doDb('mts')->loadAssoc("
            SELECT *
            FROM transactions
            WHERE user_id = {$row_user_id}
              AND customer_transaction_id = '{$row_withdrawal_id}'
        ");
        if (empty($mts_entry)) {
            $mts_entry = phive('SQL')->doDb('mts')->loadAssoc("
            SELECT *
            FROM transactions
            WHERE user_id = {$row_user_id}
              AND reference_id = '{$row_ext_id}'
        ");
        }
        $mts_entry_id = $mts_entry['id'];


        // Approve / disapprove withdrawal & update erratic details in pending_withdrawals
        $pending_withdrawal_entry_to_update = [];
        if ($row_new_status != '') {
            echo "  PW: Setting status from {$pending_withdrawal_entry['status']} to {$row_new_status}\n";

            ##########Change Made HERE###############
            #### If MTS_ID exists: set the PW approved_at = MTS updated_at, otherwise approved_at = Now() ######
            #### This timestamp reflects the response from the PSP when the txn was processed ############
            if(!empty($mts_entry)) {
                $approved_at = $mts_entry['updated_at'];
            } else {
                $approved_at = phive()->hisNow();
            }
            //--------------//---------------------//
            $pending_withdrawal_entry_id = $pending_withdrawal_entry['id'];
            $pending_withdrawal_entry_to_update = [
                'status' => $row_new_status,
                "approved_by" => $system_user->getId(),
                "approved_at" => $approved_at
                //"approved_at" => phive()->hisNow()
            ];

            $description = "Manually {$row_new_status} withdrawal with internal id of {$pending_withdrawal_entry_id} for user {$row_user_id} - {$sc_id}";
            phive('UserHandler')->logAction($row_user_id, $description, "comment");

            $description = "{$row_new_status} withdrawal by {$pending_withdrawal_entry['payment_method']} of {$pending_withdrawal_entry['amount']} with internal id of {$pending_withdrawal_entry_id} for user {$user_obj->getUsername()}";
            phive('UserHandler')->logAction($system_user, $description, 'approved-withdrawal', true, $system_user);
            phive('UserHandler')->logIp($system_user, $row_user_id, 'pending_withdrawals', $system_user->getUsername() . " " . $description, $pending_withdrawal_entry_id);
        }

        //Fix ext_id in pw
        if (empty($pending_withdrawal_entry['ext_id']) && !empty($row_ext_id) && $pending_withdrawal_entry['ext_id'] != $row_ext_id) {
            $pending_withdrawal_entry_to_update += array('ext_id' => $row_ext_id);
            echo "  PW: Setting ext_id from {$pending_withdrawal_entry['ext_id']} to {$row_ext_id}\n";
        }
        //Fix mts_id in pw
        if (empty($pending_withdrawal_entry['mts_id']) && !empty($mts_entry_id) && $pending_withdrawal_entry['mts_id'] != $mts_entry_id) {
            $pending_withdrawal_entry_to_update += array('mts_id' => $mts_entry_id);
            echo "  PW: Setting mts_id to {$mts_entry_id}\n";
        }

        if (!empty($pending_withdrawal_entry_to_update)) {
            phive("SQL")->sh($row_user_id)->updateArray(
                'pending_withdrawals',
                $pending_withdrawal_entry_to_update,
                ['id' => $pending_withdrawal_entry_id, 'user_id' => $row_user_id]
            );
        }

        // Refund a disapproved withdrawal
        if ($row_new_status == 'disapproved') {
            if ($row_refund == 'no' || $row_refund == '0') {
                echo "  CASH: Entry to disapprove not set to be refunded\n";
            } else {
                $change_balance = phive("Casino")->changeBalance($row_user_id, $pending_withdrawal_entry['amount'], "withdrawal Refund", 13);
                echo "  CASH: User refunded for disapproved entry\n";
            }
        }


        // Fix MTS
        if (empty($mts_entry)) {
            echo "  WARNING: MTS transaction not found - nothing will be updated in MTS\n";
        } else {
            $to_update_mts = [];
            if ($row_new_status == 'approved' && (int)$mts_entry['status'] !== 10) {
                $to_update_mts += array('status' => 10);
                echo "  MTS: Set status to 10 \n";
            }
            if ($row_new_status == 'disapproved' && (int)$mts_entry['status'] !== -1) {
                $to_update_mts += array('status' => -1);
                echo "  MTS: Set status to -1 \n";
            }
            if (!empty($row_ext_id) && (empty($mts_entry['reference_id']) || $mts_entry['reference_id'] != $row_ext_id)) {
                $to_update_mts += array('reference_id' => $row_ext_id);
                echo "  MTS: Set reference_id to {$row_ext_id} on \n";
            }
            if (empty($mts_entry['extra_id']) && !empty($pending_withdrawal_entry['net_account']) && $mts_entry['extra_id'] != $pending_withdrawal_entry['net_account']) {
                $to_update_mts += array('extra_id' => $pending_withdrawal_entry['net_account']);
                echo "  MTS: Set extra_id to {$pending_withdrawal_entry['net_account']}\n";
            }
            if (!empty($to_update_mts)) {
                phive('SQL')->doDb('mts')->updateArray('transactions', $to_update_mts, ['id' => $mts_entry['id']]);
                echo "  MTS transaction updated\n";
            } else {
                echo "  MTS no changes required\n";
            }
        }
    }
    echo "DONE ----- \n";
}
