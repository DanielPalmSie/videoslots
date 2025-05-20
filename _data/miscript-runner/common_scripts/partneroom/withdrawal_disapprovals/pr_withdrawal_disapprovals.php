<?php

/**
 * Disapprove a PR withdrawal and insert a Refund Transaction in PR cash_transaction table.
 *
 * @param string $sc_id            Story Id used in logging and auditing
 * @param        $disapproval_date The day to set disapproval and insert Normal Refund.
 *                                 This is usually the last day of last month.
 *                                 NOTE: Will cause issues if set before last month due to liabilities recorded in Phive.
 */
function disapprovePendingWithdrawalsPr($sc_id, $disapproval_date, $pending_withdrawal_ids)
{
    $sql = Phive('SQL');
    $uh = Phive('PRUserHandler');
    $aff = Phive('PRCasinoAffiliater');

    echo "\nDisapproving withdrawals\n";
    echo "Disapproval date: {$disapproval_date}\n";
    echo "Pending Withdrawal IDs: ";
    print_r($pending_withdrawal_ids);
    echo "--------------------------------\n";

    $callToPhiveMethod = "disapprovePendingWithdrawalsPrPhive(\$sc_id, '{$disapproval_date}', \n  [\n";
    foreach ($pending_withdrawal_ids as $pending_withdrawal_id) {
        echo "\nProcessing Pending Withdrawal ID: {$pending_withdrawal_id} \n";

        // Fetch Pending Withdrawal
        $pending_withdrawal = $uh->getPendingWithdrawalByID($pending_withdrawal_id);
        if (empty($pending_withdrawal)) {
            echo "ERROR: Pending withdrawal with id {$pending_withdrawal_id} not found !!!\n";
            continue;
        }
        $pw_user_id = $pending_withdrawal['user_id'];
        $pw_currency = $pending_withdrawal['currency'];
        $pw_amount  = $pending_withdrawal['amount'];
        echo "Partneroom User Id: {$pw_user_id} \n";
        echo "Pending Withdrawal Amount: {$pw_currency} {$pw_amount} \n";

        //Fetch user and company
        $user = $uh->getUserByID($pw_user_id); // only for logging
        $company = $uh->getUserCompany($pw_user_id);
        if (empty($company)) {
            echo "ERROR: Company for  {$pw_user_id} not found !!!\n";
            continue;
        }
        $company_id = $company['company_id'];
        $company_country = $company['country'];
        echo "Partneroom Company ID (user_id in Phive):{$company_id} with initial cash_balance: {$company['cash_balance']}\n";
        $callToPhiveMethod .= "   ['user_id' => {$company_id}, 'currency' => '{$pw_currency}', 'amount' => {$pw_amount}, 'country' => '{$company_country}'], // pending_withdrawal.id: {$pending_withdrawal_id} username: {$user['username']}\n";

        //Disapprove pending withdrawal
        echo "Disapproving Pending Withdrawal ID:{$pending_withdrawal_id}   Status:{$pending_withdrawal['status']}   Approved_by:{$pending_withdrawal['approved_by']}  approved_at:{$pending_withdrawal['approved_at']}\n";
        $to_update = [
            "status" => "disapproved",
            "approved_by" => 5000001,
            "approved_at" => $disapproval_date
        ];
        $sql->updateArray('pending_withdrawals', $to_update, ['id' => $pending_withdrawal_id]);
        $pending_withdrawal = $uh->getPendingWithdrawalByID($pending_withdrawal_id);
        echo "Disapproved Pending Withdrawal ID:{$pending_withdrawal_id}   Status:{$pending_withdrawal['status']}   Approved_by:{$pending_withdrawal['approved_by']}  approved_at:{$pending_withdrawal['approved_at']}\n";

        // Create normal refund, and update timestamp to $disapproval_date
        $refund_cash_tx_id = $aff->transactUser($pw_user_id, $pw_amount, 13, 2);
        $refund_cash_tx = $sql->loadArray("SELECT * FROM cash_transactions WHERE transaction_id = {$refund_cash_tx_id}");
        if (empty($refund_cash_tx)) {
            echo "ERROR: Refund Cash Tx not found to update refund date. !! \n";
            continue;
        } else {
            $sql->updateArray('cash_transactions', ['timestamp' => $disapproval_date], ['transaction_id' => $refund_cash_tx_id]);
        }
        $refund_cash_tx = $sql->loadArray("SELECT * FROM cash_transactions WHERE transaction_id = {$refund_cash_tx_id}")[0];
        echo "Refund generated in cash_transactions transaction_id:{$refund_cash_tx['transaction_id']} " .
            "user_id:{$refund_cash_tx['user_id']} " .
            "amount:{$refund_cash_tx['currency']} {$refund_cash_tx['amount']} " .
            "description:{$refund_cash_tx['description']} " .
            "timestamp:{$refund_cash_tx['timestamp']} " .
            "transactiontype:{$refund_cash_tx['transactiontype']} \n";

        // update cash_transaction of original withdrawal checked = 2
        $original_cash_tx_withdrawal = $sql->loadArray("SELECT * FROM cash_transactions WHERE entry_id = {$pending_withdrawal_id}");
        if (empty($original_cash_tx_withdrawal)) {
            echo "ERROR: Original_cash_tx_withdrawal not found to update refund date. !! \n";
            continue;
        }
        $sql->updateArray('cash_transactions', ['checked' => 2], ['entry_id' => $pending_withdrawal_id]);
        $original_cash_tx_withdrawal = $sql->loadArray("SELECT * FROM cash_transactions WHERE entry_id = {$pending_withdrawal_id}");
        echo "Original_cash_tx_withdrawal transaction_id:{$original_cash_tx_withdrawal[0]['transaction_id']} updated checked to value 2: [{$original_cash_tx_withdrawal[0]['checked']}] \n";

        // Display new company balance
        $company = $uh->getUserCompany($pw_user_id);
        echo "Partneroom Company ID (user_id in Phive):{$company['company_id']} now has a cash_balance: {$company['cash_balance']}\n";
    }

    // Display the counterpart call needed on Phive side
    echo "\n";
    echo "--------------------------------\n";
    echo "CALL TO PHIVE LIBRARY           \n";
    echo "--------------------------------\n";
    echo $callToPhiveMethod . "  ]\n);    \n";
    echo "--------------------------------\n";
}
