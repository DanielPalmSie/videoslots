<?php

use Videoslots\HistoryMessages\Exceptions\InvalidMessageDataException;
use Videoslots\HistoryMessages\DepositHistoryMessage;
use Exceptions;

/**
 * Loops through a list of deposits to credit them and update the status and reference_id in mts.
 *
 * @param array $deposits
 * @param string $sc_id
 * @param bool $consider_blocks
 * @param bool $save_results
 * @return bool
 */
function creditDeposits(string $sc_id, array $deposits, bool $consider_blocks = false, bool $save_results = false): bool
{
    $uh = Phive('UserHandler');
    $system_user = cu('system');

    $upd = 0;
    $to_save_results = [];
    $result_counter = 0;
    foreach ($deposits as $deposit) {
        $user_id = $deposit['user_id'];
        $to_save_results[$result_counter] = $deposit;
        $to_save_results[$result_counter]['result'] = "Started";
        echo "{$user_id} ";

        $reference_id = $deposit['reference_id'];
        if (empty($reference_id)) {
            $to_save_results['result'] = "Missing reference_id";
            $result_counter++;
            echo "no reference_id added, it is mandatory \n";
            continue;
        }

        $amount = $deposit['amount'];
        if (empty($amount)) {
            $to_save_results['result'] = "Missing amount";
            $result_counter++;
            echo "no amount added, it is mandatory \n";
            continue;
        }

        $supplier = $deposit['supplier'];
        if (empty($supplier)) {
            $to_save_results['result'] = "Missing supplier";
            $result_counter++;
            echo "no supplier added, it is mandatory \n";
            continue;
        }

        $user_obj = cu($user_id);
        if (empty($user_obj)) {
            $to_save_results['result'] = "User not found";
            $result_counter++;
            echo "user not found\n";
            continue;
        }

        // At the moment it has been requested to ignore all blocks
        if ($consider_blocks) {
            if ($user_obj->hasSetting('deposit_block')) {
                $to_save_results['result'] = "deposit_blocked";
                $result_counter++;
                echo "won't be credited because is deposit_blocked \n";
                continue;
            }

            if ($user_obj->hasSetting('super-blocked')) {
                $to_save_results['result'] = "super-blocked";
                $result_counter++;
                echo "won't be credited because is super-blocked \n";
                continue;
            }
        }

        $deposits_exists =  checkIfMtsTransactionExistsOnDeposits($deposit['mts_id'], $user_id);
        if(!empty($deposits_exists)){
            $to_save_results['result'] = "Deposit already Exists";
            $result_counter++;
            echo "Deposit won't be credited because mts_id already exists on deposits table \n";
            continue;
        }

        $user_country = $user_obj->getCountry();
        $status = $user_obj->getSetting('current_status');
        if ($user_country == 'ES') {
            // Will be used later to credit only those with status ACTIVE and PENDING_VERIFICATION
            if (!($status == 'ACTIVE' || $status == 'PENDING_VERIFICATION')) {
                echo "is from Spain and the current status is {$status}, hence won't be credited  \n";
                $to_save_results['result'] = "User is from ES and status is: {$status}";
                $result_counter++;
                continue;
            }
        }

        // TODO: find a solution for IT, needs to go to history/adm
        if ($user_country == 'IT') {
            $to_save_results['result'] = "User is from IT, not credited";
            $result_counter++;
            echo "is from IT, hence won't be credited  \n";
            continue;
        }

        if (!empty($deposit['mts_id'])) {
            $mts_id = $deposit['mts_id'];
            $mts_result = getMtsTransactionById($mts_id);
            $transaction_details = $mts_result;
            if (empty($transaction_details)) {
                $to_save_results['result'] = "mts transaction not found";
                $result_counter++;
                echo "{$mts_id} mts transaction not found based on id when one given\n";
                continue;
            }
        } else {
            $transaction_details = $deposit;
            $mts_id = 0;
        }

        $deposit_result = phive('Casino')->depositCash(
            $user_id,
            $amount,
            $supplier,
            $reference_id,
            strtolower($transaction_details['sub_supplier']),
            '',
            '',
            false,
            'approved',
            null,
            $mts_id,
            ucfirst(strtolower($transaction_details['sub_supplier']))
        );

        if ((string)$deposit_result !== 'deposit_exists') {
            $log_description = "system deposited {$amount} to {$user_id} - {$sc_id}";

            $deposit_transaction = getDepositByUserAndReferenceId($user_id, $reference_id);
            $uh->logIp($system_user, $user_id, 'deposits', $log_description, $deposit_transaction['id']);
            echo " - Deposit created with internal id {$deposit_transaction['id']}";

            $ct_id = getLastDepositCashTransactionByUserAndAmount($user_id, $amount)['id'];
            $uh->logIp($system_user, $user_id, 'cash_transactions', $log_description, $ct_id);
            echo " - Cash transaction created with id {$ct_id} ";
            $uh->logAction($user_id, $log_description, "comment");

            if (!empty($mts_result)) {
                if ($transaction_details['status'] != 10) {
                    $status = 10;
                    updateMtsTransactionStatus((int)$mts_id, $status);
                    updateMtsTransactionReferenceId((int)$mts_id, (string)$reference_id);
                    echo " - Updated MTS id {$mts_id} to status to 10";
                }
            }
            $upd++;

            try {
                $args = [
                    'transaction_id' => (int)$deposit_transaction['id'],
                    'user_id' => (int)$deposit_transaction['user_id'],
                    'reference_id' => (string)$deposit_transaction['ext_id'],
                    'amount' => (int)$deposit_transaction['amount'],
                    'currency' => (string)$deposit_transaction['currency'],
                    'sub_supplier' => (string)$deposit_transaction['scheme'] ?? '',
                    'supplier' => (string)$deposit_transaction['dep_type'],
                    'supplier_display' => (string)$deposit_transaction['display_name'] ?? '',
                    'card_id' => 0,
                    'card_num' => (string)$deposit_transaction['card_hash'] ?? '',
                    'card_duplicate' => [],
                    'type' => (string)$deposit_transaction['dep_type'] ?? '',
                    'new_card' => null,
                    'extra' => ['type' => 'deposit'],
                    'ip' => $user_obj->getAttr('cur_ip'),
                    'event_timestamp' => time(),
                ];

                phive('Licensed')->addRecordToHistory(
                    'deposit',
                    new DepositHistoryMessage($args)
                );
            } catch (InvalidMessageDataException $exception) {
                phive('Logger')
                    ->getLogger('history_message')
                    ->error("Invalid message data exception on misc-script-runner/.../deposits.php",
                        [
                            'report_type' => 'deposit',
                            'args' => $args,
                            'validation_errors' => $exception->getErrors(),
                            'user_id' => $user_obj->getId(),
                        ]);
            } catch (Exception $exception) {
                phive('Logger')
                    ->getLogger('history_message')
                    ->error($exception->getMessage(), $args);
            }

        } else {
            $to_save_results['result'] = "Deposit already exists";
            $result_counter++;
            echo " - Deposit already exists\n";
            continue;
        }
        $result_counter++;
        echo "\n";
    }
    echo "\n Processed {$upd} out of " . count($deposits) . " total transactions \n";
    if ($save_results) {
        saveCsv($to_save_results, __DIR__ . "/script_results.csv");
    }

    return true;
}

/**
 * Checks a list of deposits for mts id and to validate they don't exists in the db.
 *
 * @param string $csv_path_for_transactions_to_check
 * @return array
 */
function initialChecks(string $csv_path_for_transactions_to_check): array
{
    $to_save = [];
    $to_save_all_transactions = [];
    $to_save_counter = 0;
    $to_check = readCsv($csv_path_for_transactions_to_check);
    $mts_export_import = "";
    foreach ($to_check as $item) {
        $user_id = $item['user_id'];
        $amount = $item['amount'];
        $reference_id = $item['reference_id'];
        $to_save[$to_save_counter] = $item;
        $to_save[$to_save_counter]['supplier'] = "";
        $to_save[$to_save_counter]['sub_supplier'] = "";
        $to_save[$to_save_counter]['deposit_exists'] = "";
        if (empty($item['user_id']) || empty($item['amount']) || empty($item['reference_id'])) {
            $to_save[$to_save_counter]['mts_id'] = "Missing mandatory columns";
        } else {
            $mts_transaction = getMtsTransactionByUserAndReferenceId($user_id, $reference_id);
            $mts_id = $mts_transaction['id'];
            $to_save[$to_save_counter]['mts_id'] = $mts_id;
            $to_save[$to_save_counter]['supplier'] = $mts_transaction['supplier'];
            $deposit_exists = getDepositByUserAndReferenceId($user_id, $reference_id);
            $to_save[$to_save_counter]['deposit_exists'] = !empty($deposit_exists) ? "yes" : "no";
            $mts_export_import .= !empty($mts_id) ? "{$mts_id}, " : "";

            if (empty($mts_id)) {
                $all_transactions_for_user = getAllMtsTransactionsWithUserAndAmount($user_id, $amount);
                foreach ($all_transactions_for_user as $transaction) {
                    $to_save_all_transactions[] = $transaction;
                }
            }
        }
        $to_save_counter++;
    }

    saveCsv($to_save, __DIR__ . "/deposits_to_credit.csv");
    saveCsv($to_save_all_transactions, __DIR__ . "/all_deposits.csv");
    $mts_export_import = substr($mts_export_import, 0, -2);
    echo "\nTo import mts transactions:\n";
    echo "\"transactions\": \"id IN ({$mts_export_import})\"\n";

    return $to_save;
}

/**
 * After crediting the deposits it validates if the deposit is eventually inserted and
 * the related mts transaction's status and reference_id is set correctly.
 *
 * @param string $csv_path_to_transaction_results
 * @return array
 */
function postDeployChecks(string $csv_path_to_transaction_results): array
{
    $sql = Phive('SQL');
    $to_save = [];
    $to_save_counter = 0;
    $to_check = readCsv($csv_path_to_transaction_results);
    foreach ($to_check as $item) {
        $user_id = $item['user_id'];
        $amount = $item['amount'];
        $reference_id = $item['reference_id'];
        $mts_id = $item['mts_id'];
        $to_save[$to_save_counter] = $item;

        if (!empty($mts_id)) {
            $mts_transaction = $sql->doDb('mts')->loadAssoc("SELECT * FROM transactions WHERE id = {$mts_id}");
            $to_save[$to_save_counter]['mts_id_ok'] = empty($mts_transaction) ? "No transaction found" : "OK";
            $to_save[$to_save_counter]['mts_status_ok'] = $mts_transaction['status'] !== 10 ? "Status not 10" : "OK";
            $to_save[$to_save_counter]['mts_reference_id_ok'] = $mts_transaction['reference_id'] !== $reference_id ? "reference_id not matching" : "OK";
        } else {
            $to_save[$to_save_counter]['mts_id_ok'] = "OK";
            $to_save[$to_save_counter]['mts_status_ok'] = "OK";
            $to_save[$to_save_counter]['mts_reference_id_ok'] = "OK";
        }

        $deposit = getDepositByUserAndReferenceId($user_id, $reference_id);
        $to_save[$to_save_counter]['deposit_ok'] = empty($deposit) ? "Deposit not found" : "OK";
        $to_save_counter++;
    }
    saveCsv($to_save, __DIR__ . "/deposits_post_deploy_check_results.csv");
}

/**
 * Gets one mts transaction by id.
 *
 * @param int $mts_id
 * @return mixed
 */
function getMtsTransactionById(int $mts_id)
{
    return Phive('SQL')->doDb('mts')->loadAssoc("
        SELECT *
        FROM transactions
        WHERE id = {$mts_id};
    ");
}

/**
 * Gets a deposit based on user_id and reference_id.
 *
 * @param int $user_id
 * @param string $reference_id
 * @return mixed
 */
function getDepositByUserAndReferenceId(int $user_id, string $reference_id)
{
    return Phive('SQL')->sh($user_id)->loadAssoc("
                SELECT *
                FROM deposits
                WHERE ext_id = {$reference_id}
                  AND user_id = {$user_id}
                ORDER BY ID DESC
                LIMIT 1
    ");
}

/**
 * Checks for the last deposit cash_transaction of the user based on user_id.
 *
 * @param int $user_id
 * @param int $amount
 * @return mixed
 */
function getLastDepositCashTransactionByUserAndAmount(int $user_id, int $amount)
{
    return Phive('SQL')->sh($user_id)->loadAssoc("
        SELECT id
        FROM cash_transactions
        WHERE amount = {$amount}
          AND transactiontype = 3
          AND user_id = {$user_id}
        ORDER BY ID DESC
        LIMIT 1
    ");
}

/**
 * Gets mts transactions based on user_id and reference_id.
 *
 * @param int $user_id
 * @param string $reference_id
 * @return mixed
 */
function getMtsTransactionByUserAndReferenceId(int $user_id, string $reference_id)
{
    return Phive('SQL')->doDb('mts')->loadAssoc("
        SELECT *
        FROM transactions
        WHERE user_id = {$user_id}
          AND reference_id = {$reference_id}
          AND type = 0;
    ");
}

/**
 * Gets all mts deposit transactions based on user_id and exact amount.
 *
 * @param int $user_id
 * @param int $amount
 * @return mixed
 */
function getAllMtsTransactionsWithUserAndAmount(int $user_id, int $amount)
{
    return Phive('SQL')->doDb('mts')->loadArray("
        SELECT id, type, customer_id, user_id, reference_id, amount, currency, supplier, sub_supplier, status, created_at, updated_at
        FROM transactions
        WHERE user_id = {$user_id}
          AND amount = {$amount}
          AND type = 0;
    ");
}

/**
 * Updates mts transaction status for one row based on id.
 *
 * @param int $mts_id
 * @param int $status
 * @return mixed
 */
function updateMtsTransactionStatus(int $mts_id, int $status)
{
    return Phive('SQL')->doDb('mts')->query("
        UPDATE transactions
        SET status = {$status}
        WHERE id = {$mts_id}
    ");
}

/**
 * Updates mts transaction reference_id for one row based on id.
 *
 * @param int $mts_id
 * @param string $reference_id
 * @return mixed
 */
function updateMtsTransactionReferenceId(int $mts_id, string $reference_id)
{
    return Phive('SQL')->doDb('mts')->query("
        UPDATE transactions
        SET reference_id = '{$reference_id}'
        WHERE id = {$mts_id}
    ");
}

/**
 * Checks if the mts transaction ID already exists on deposits table based on mts_id.
 *
 * @param int $mts_id
 * @param int $user_id
 * @return mixed
 */
function checkIfMtsTransactionExistsOnDeposits(int $mts_id, int $user_id)
{
    return Phive('SQL')->sh($user_id)->loadAssoc("
        SELECT mts_id
        FROM deposits
        WHERE mts_id = {$mts_id}");
}
