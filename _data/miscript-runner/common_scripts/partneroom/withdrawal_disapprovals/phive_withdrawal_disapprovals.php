<?php

/**
 * Disapprove a PR withdrawal. On phive side we:
 *   - Add the withdrawal amount to $curr_users_daily_balance_stat where days > $disapproval_date
 *   - Inserting new liability for the user in the month of $disapproval_date
 * @param string $sc_id Story Id used in logging and auditing
 * @param date   $disapproval_date The day to set disapproval and insert Normal Refund.
 *                                 This is usually the last day of last month.
 *                                 NOTE: Will cause issues if set before last month due to liabilities recorded in Phive.
 * @param array $pending_withdrawals array of pending withdrwals details [['user_id' => 123, 'currency' => 'EUR', 'amount' => 111, 'country' => 'MT'],[...]]
 */
function disapprovePendingWithdrawalsPrPhive($sc_id, $disapproval_date, $pending_withdrawals)
{

    $sql = Phive('SQL');
    $year =  explode("-", $disapproval_date)[0];
    $month =  explode("-", $disapproval_date)[1];

    foreach ($pending_withdrawals as $pending_withdrawal_details) {

        $pending_withdrawal_user_id = $pending_withdrawal_details['user_id'];
        $pending_withdrawals_currency = $pending_withdrawal_details['currency'];
        $pending_withdrawals_amount = $pending_withdrawal_details['amount'];
        $pending_withdrawals_country = $pending_withdrawal_details['country'];

        echo "\nProcessing withdrawal User id: {$pending_withdrawal_user_id} {$pending_withdrawals_country} {$pending_withdrawals_currency} {$pending_withdrawals_amount}\n";

        // Add the withdrawal amount to $curr_users_daily_balance_stat where days > $disapproval_date
        $curr_users_daily_balance_stats = $sql->loadArray("select date,currency, cash_balance, country " .
            "from users_daily_balance_stats " .
            "where user_id = {$pending_withdrawal_user_id} " .
            "and currency = '{$pending_withdrawals_currency}' " .
            "and date >= '{$disapproval_date}'");
        echo "User ID {$pending_withdrawal_user_id}: Current users_daily_balance_stats: \n";
        foreach ($curr_users_daily_balance_stats as $curr_users_daily_balance_stat) {
            echo $curr_users_daily_balance_stat['country'] . " " .
                $curr_users_daily_balance_stat['date'] . ": " .
                $curr_users_daily_balance_stat['currency'] . " " .
                $curr_users_daily_balance_stat['cash_balance'] . "\n";
        }

        $sql->query("update users_daily_balance_stats set cash_balance = cash_balance + {$pending_withdrawals_amount} " .
            "where user_id = {$pending_withdrawal_user_id} " .
            "and currency = '{$pending_withdrawals_currency}' " .
            "and date > '{$disapproval_date}'");

        $curr_users_daily_balance_stats = $sql->loadArray("select date,currency, cash_balance, country " .
            "from users_daily_balance_stats " .
            "where user_id = {$pending_withdrawal_user_id} " .
            "and currency = '{$pending_withdrawals_currency}' " .
            "and date >= '{$disapproval_date}'");
        echo "User ID {$pending_withdrawal_user_id}: Updated users_daily_balance_stats > {$disapproval_date} : \n";
        foreach ($curr_users_daily_balance_stats as $curr_users_daily_balance_stat) {
            echo $curr_users_daily_balance_stat['country'] . " " .
                $curr_users_daily_balance_stat['date'] . ": " .
                $curr_users_daily_balance_stat['currency'] . " " .
                $curr_users_daily_balance_stat['cash_balance'] . "\n";
        }

        // inserting new liability
        $to_insert = [
            'year' => $year,
            'month' => $month,
            'user_id' => $pending_withdrawal_user_id,
            'type' => 'credit',
            'main_cat' => 15,
            'sub_cat' => 13,
            'transactions' => '1',
            'amount' => $pending_withdrawals_amount,
            'currency' => $pending_withdrawals_currency,
            'country' => $pending_withdrawals_country,
            'source' => 1
        ];
        phive("SQL")->insertArray('users_monthly_liability', $to_insert);

        echo "Liability Inserted \n";
        $liabilities = $sql->loadArray("select year, month, type, currency, amount " .
            "from users_monthly_liability " .
            "where user_id = {$pending_withdrawal_user_id} " .
            "and year = '{$year}' " .
            "and month = '{$month}'");
        foreach ($liabilities as $liability) {
            echo $liability['year'] . " " .
                $liability['month'] . " " .
                $liability['type'] . " " .
                $liability['currency'] . " " .
                $liability['amount'] . "\n";
        }
    }
}
