<?php

/**
 * Loops through a list of self-excluded GB users, and if inactive and balance unchanged, zero out balance.
 * Generates a report (csv) of actions, for donations to YGAM.
 *
 * @param string $sc_id         Story Id used in logging and auditing
 * @param string $full_csv_path The CSV file name (including full path) to be processed
 *                              CSV Columns: user_id,cash_balance,currency,country,Internal exclusion requested,Internal exclusion will end,External exclusion received
 * @param boolean $test         Will not change balance if test = true
 */
function donateSelfExcludedBalances($sc_id, $full_csv_path, $test = true)
{
    echo "Zero balances of self-excluded GB players from csv, generating a report of data updated\n";
    echo "-------------\n";

    $system_user = cu("system");

    $accounts_zeroed = [];

    $data = readCsv($full_csv_path);
    $i = 0;
    $updated = 0;

    foreach ($data as $item) {
        $user_id = $item['id'];
        echo "{$user_id} - ";
        $active = phive('UserHandler')->getFreshAttr($user_id, 'active');
        $cash_balance = phive('UserHandler')->getFreshAttr($user_id, 'cash_balance');

        $accounts_zeroed[$i]['user_id'] = $user_id;
        $accounts_zeroed[$i]['currency'] = $item['currency'];
        $accounts_zeroed[$i]['balance_in_file'] = $item['cash_balance'];
        $accounts_zeroed[$i]['balance_in_system'] = $cash_balance;
        $accounts_zeroed[$i]['active'] = $active;
        if ((int)$active !== 0) {
            $accounts_zeroed[$i]['account_zeroed'] = 'user is active now, no changes done';
        } else {
            $tolerance = 100; // 1 GBP tolerance
            $balance_difference = abs((float)$cash_balance - (float)$item['cash_balance']);
            if ($balance_difference > $tolerance) {
                $accounts_zeroed[$i]['account_zeroed'] = 'cash_balance changed beyond tolerance, no changes done';
            } else {
                if ($test) {
                    $accounts_zeroed[$i]['account_zeroed'] = 'testing - will be updated';
                } else {
                    $description = "Zeroing out because of self-exclusion - donation to YGAM";
                    $deduct_amount = (-1) * $cash_balance;
                    phive("Casino")->changeBalance($user_id, $deduct_amount, $description, 13);
                    phive('UserHandler')->logAction($user_id, $description . " - {$sc_id}", "comment", true, $system_user);
                    $accounts_zeroed[$i]['account_zeroed'] = 'yes';
                }
                $updated++;
            }
        }
        echo "{$accounts_zeroed[$i]['account_zeroed']}\n";
        $i++;
    }

    echo "\nUpdated {$updated} out of {$i} users \n\n";

    $outputFile = substr($full_csv_path, 0, strlen($full_csv_path) - 4) . "_updated_list.csv";
    saveCSV($accounts_zeroed, $outputFile);

    echo "Please add the new file {$outputFile} from the script folder to the story. Thanks \n\n";

    echo "DONE ----- \n";
}
