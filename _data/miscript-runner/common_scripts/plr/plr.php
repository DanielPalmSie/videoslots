<?php

/**
 * Inserts liability adjustments from a csv file.
 *
 * @param string $sc_id         Story Id used in logging and auditing
 * @param string $full_csv_path The CSV file name (including full path) to be processed
 *                              CSV Columns:user_id, [currency],[username],[net_liab],[opening],[closing],[diff],[abs_diff],[abs_diff_in_eur],[mod], country,province,[adjustment_amount],[type],description,[total_liability_amount],specific_liability_amount,liability_type,[details]
 * @param  int $year            Year
 * @param  int $month           & month to process
 */
function insertLiabilityAdjustments($sc_id, $full_csv_path, int $year, int $month)
{
    echo "\nInserting liability adjustments from {$full_csv_path} for {$year} {$month}  {$sc_id}\n";
    $sql = Phive('SQL');

    $cash_tx_timestamp = "{$year}-{$month}-01 01:01:01";

    $to_insert = readCsv($full_csv_path);
    foreach ($to_insert as $r) {
        $user_id = $r['user_id'];
        $user = cu($user_id);
        $currency = $user->getAttribute('currency');
        $amount = $r['specific_liability_amount'];
        $type = $amount > 0 ? 'credit' : 'debit';
        $description = $r['description'];
        $country = $user->getAttribute('country');
        $province =  $r['province'];

        echo "Inserting PLR liability UserId:[{$user_id}]  Amount:[{$type} {$currency} {$amount}]  Description:[{$description}] ... ";
        $uml_insert = [
            'year' => $year,
            'month' => $month,
            'user_id' => $user_id,
            'type' => $type,
            'main_cat' => $province == 'ON' ? 24 : 28, //Main Cat 24 for Ontario
            'sub_cat' => $description,
            'transactions' => 1,
            'amount' => $amount,
            'currency' => $currency,
            'country' => $country,
            'source' => 0
        ];
        $sql->sh($user_id)->insertArray('users_monthly_liability', $uml_insert);
        $sql->insertArray('users_monthly_liability', $uml_insert);
        echo "Inserted\n";

        if ($province == 'ON') {
            echo "  NOT inserting cash transaction for ON user {$user_id} \n";
//      Trustly issues are not actually liabilites, but if we do not insert cash transaction liability is still found, so for now we leave as it was before.
//	  } else if ($r['liability_type'] == 'trustly') {
//            echo "  NOT inserting cash transaction for Trustly Liability {$user_id} \n";
        } else {
            echo "Inserting Type 91 cash transaction dated [{$cash_tx_timestamp}] for UserId:[{$user_id}]  Amount:[{$currency} {$amount}]  Description:[{$description}] ... ";
            $sql->sh($user_id)->insertArray('cash_transactions', [
                'user_id' => $user_id,
                'amount' => $amount,
                'description' => $description,
                'timestamp' => $cash_tx_timestamp,
                'transactiontype' => 91,
                'currency' => $currency,
                'bonus_id' => 0,
                'balance' => 0,
                'entry_id' => 0,
                'session_id' => 0,
                'parent_id' => 0
            ]);
            echo "Inserted\n";
        }
    }

    echo "DONE ----- \n";
}


/**
 * Moves Jackpot Wins to Win category in the PLR for a given year and month and marks them with the network.
 * This is needed because most jackpot wins are registered under wins and therefore needs to be unified.
 *
 * @param  string $sc_id  Story Id used in logging and auditing
 * @param  int $year      Year
 * @param  int $month     & month to process
 */
function recategorizeJackpotWinsAsNormalWin($sc_id, $year, $month): bool
{
    echo "\nBulk updating Jackpot Wins (22) to Win category (5), setting network pragmatic {$sc_id}\n";
    $sql = Phive('SQL');
    $set = ['main_cat' => 5, 'sub_cat' => 'pragmatic'];
    $where = ['source' => 0, 'year' => $year, 'month' => $month, 'main_cat' => 22];
    $sql->updateArray('users_monthly_liability', $set, $where);
    $sql->shs()->updateArray('users_monthly_liability', $set, $where);
    echo "DONE ----- \n";
}

/**
 * Update the cache_value of liability-report-adjusted-month and reports-last-users_monthly_liability to last month.
 * This is executed for VS & MRV after monthly PLR process is completed by finance team
 *
 * @param  string $sc_id  Story Id used in logging and auditing
 */
function updatMiscCacheLiabilityValuesToLastMonth($sc_id)
{
    $lastMonth = date("Y-m", strtotime("-1 month"));

    echo "Updating misc_cache liability records to {$lastMonth} {$sc_id}\n\n";
    $sql = Phive('SQL');
    $checkQuery = "select id_str, cache_value from  misc_cache  WHERE id_str like '%liability%'";
    $old_values = phive('SQL')->loadArray($checkQuery);

    $sql->query("UPDATE misc_cache SET cache_value = '{$lastMonth}' WHERE id_str like '%liability%';");

    $new_values = phive('SQL')->loadArray($checkQuery);
    echo "Updated {$old_values[0]['id_str']} from [{$old_values[0]['cache_value']}] to [{$new_values[0]['cache_value']}] \n";
    echo "Updated {$old_values[1]['id_str']} from [{$old_values[1]['cache_value']}] to [{$new_values[1]['cache_value']}] \n";

    echo "DONE ----- \n";
}
