<?php


class Booster
{
    /** @var SQL $db */
    protected $db;

    /** @var Config $config */
    protected $config;

    /** @var Casino $casino */
    protected $casino;

    public static $vault_key = 'booster_vault';

    public static $opt_out_key = 'disabled_booster_vault';

    /**
     * This key will be concatenated to the user_id to avoid hitting the DB on every bet checking for the same setting.
     * TTL = 30min
     */
    public static $redis_vault_key = 'has_booster_vault_';

    public function __construct()
    {
        $this->db = phive('SQL');
        $this->config = phive('Config');
        $this->casino = phive('Casino');
    }

    /**
     * Returns true if we should do the normal weekend booster and false to deduct the portion of the winnings for the booster
     *
     * @param DBUser $user
     * @return bool
     */
    public function doBoosterVault($user)
    {
        if(licSetting('excluded_from_booster_vault', $user)){
            return false;
        }
        
        return in_array($user->getCountry(), phive('DBUserHandler')->getSetting('booster_vault_countries',[])) || $this->hasBoosterVault($user);
    }

    /**
     * Return if the user has the setting for the booster vault ($vault_key)
     * This is used to enable the new booster even for user that are not in the "booster_vault_countries"
     *
     * @param DBUser $user
     * @return mixed
     */
    public function hasBoosterVault($user)
    {
        if(licSetting('excluded_from_booster_vault', $user)){
            return false;
        }
        
        $redis_key = self::$redis_vault_key.$user->getId();
        $res = phQget($redis_key);
        if(!empty($res)) {
            return $res;
        }

        $res = $user->hasSetting(self::$vault_key);
        phQset($redis_key, $res, 1800); // 30min

        return $res;
    }

    /**
     * We set the customer to booster vault on customers that should have the offer
     *
     * @param DBUser $user
     */
    public function initBoosterVault($user)
    {
        if(licSetting('excluded_from_booster_vault', $user)){
            return false;
        }
        
        if (licSetting('booster_vault_on_registration', $user) === true){
            $user->setMissingSetting(self::$vault_key, 0);
        }
    }

    /**
     * We update the booster promotion to the new one if they haven't deposited in the last X days
     *
     * @param int $days Number of days they haven't deposited
     */
    public function updateBoosterCron($days = 183)
    {        
        if (phive('DBUserHandler')->getSetting('booster_vault_auto_move', true) !== true || licSetting('excluded_from_booster_vault', $user)) {
            return;
        }
        
        $query = "SELECT u.id, u.country, us.value, IFNULL(SUM(uds.deposits),0) as dep_sum
                    FROM users u
                             LEFT JOIN users_settings us ON us.user_id = u.id AND us.setting = '". self::$vault_key ."'
                             LEFT JOIN users_daily_stats uds ON uds.user_id = u.id AND uds.date > CURDATE() - INTERVAL {$days} DAY
                    WHERE us.value IS NULL
                    GROUP BY u.id
                    HAVING dep_sum = 0;";

        $user_list = phive('SQL')->shs()->loadArray($query);

        foreach ($user_list as $row) {
            $this->initBoosterVault(cu($row['id']));
        }
    }

    /**
     * TODO double check rounding during test
     *
     * @param DBUser $user
     * @param mixed $win_amount
     * @param mixed $win_id
     * @return bool
     */
    public function transferWinAmount($user, $win_amount, $win_id)
    {
        if (empty($win_id) || $this->doBoosterVault($user) === false || $this->optedOutToVault($user) === true) {
            return false;
        }

        $percentage = $this->config->getValue('weekend-booster-win-vault', 'percentage', 0.5);
        $min_transfer = $this->config->getValue('weekend-booster-win-vault', 'min-transfer', 1);

        $to_booster = $win_amount * $percentage / 100;

        if ($to_booster < $min_transfer) {
            return false;
        }

        $to_booster = round($to_booster);

        $result = $this->casino->changeBalance($user, -$to_booster, 'Transfer to Booster Vault', 100, '', 0, 0, false, $win_id);

        if ($result === false) {
            return false;
        }

        return $user->incSetting(self::$vault_key, $to_booster);
    }

    /**
     * @param DBUser $user
     * @return bool
     */
    public function getVaultBalance($user = null)
    {
        $user = cu($user);
        if (empty($user)) {
            return false;
        }

        return $user->getSetting(self::$vault_key);
    }

    /**
     * @param DBUser $user
     * @param bool $auto
     * @param integer $amount - this will be used ONLY when $auto = true, to enforce a release of only the $amount that the player earned from the previous week
     * @param bool $skip_notify
     * @param null $marketing_blocked
     * @param string $sql
     * @param bool $is_single_release
     * @return bool
     */
    public function releaseBoosterVault($user = null, $auto = false, $amount = 0, $skip_notify = false, $marketing_blocked = null, $sql = null, $is_single_release = false)
    {
        $user = cu($user);
        if (empty($user)) {
            return false;
        }
        $user->marketing_blocked = $marketing_blocked;
        phive('Localizer')->setLanguage($user->getLang(), true);

        // Partial release of amount is allowed only in AUTO mode.
        if(!empty($amount) && !empty($auto)) {
            if ((int)$amount > (int)$this->getVaultBalance($user)) {
                return false;
            }
            $vault_amount = $amount;
        } else {
            $vault_amount = (int)$this->getVaultBalance($user);
        }

        // check if result amount is negative
        $end_result = $user->getSetting(self::$vault_key) + -$vault_amount;
        if ($end_result < 0) {
            phive('Logger')->getLogger('payments')->warning("Negative Value Transfer from Booster Vault", [
                'vault_key' => self::$vault_key,
                'user' => $user,
                'result' => $end_result,
                'value_amount' => $vault_amount,
                'booster_vault' => $user->getSetting(self::$vault_key),
                'sql' => $sql,
                'is_single_release' => $is_single_release
            ]);

            $text = $user->getId()." released $vault_amount when he had {$user->getSetting(self::$vault_key)}";
            $email = phive('MailHandler2')->getSetting("tech_support", 'techsupport@videoslots.com');
            phive('MailHandler2')->saveRawMail('Negative balance in vault', $text, '', $email);

            return false;
        }

        if ($vault_amount > 0) {
            $result = $this->casino->changeBalance($user, $vault_amount, "Transfer from Booster Vault {$vault_amount} with new end balance {$end_result}", 101, false);

            if ($result !== false) {
                $result = $user->incSetting(self::$vault_key, -$vault_amount);
                phive('UserHandler')->logAction($user, "Booster vault {$vault_amount} cents transferred to balance with new end balance {$result}", 'booster-vault', true);
                uEvent('transfervault', $vault_amount, '', '', $user->data);
                if (!empty($auto)) {
                    if (empty($skip_notify)) {
                        phive('CasinoCashier')->notifyUserTransaction(101, $user, $vault_amount, true);
                    }
                } else {
                    toWs(['msg' => t('my.booster.vault.get.funds.credited'), 'success' => true], 'booster-release', $user->getId());
                }
                phive()->dumpTbl('booster-release', ['auto' => $auto, 'amount' => $amount, 'skip_notify'=> $skip_notify, 'marketing_blocked' => $marketing_blocked], $user);
                return $amount;
            }
            return $result;
        } else {
            if (empty($auto)) {
                toWs(['msg' => t('my.booster.no.funds.available'), 'success' => false], 'booster-release', $user->getId());
            }
        }

        return false;
    }

    /**
     * IMPORTANT this function is not part of the normal booster process check the script
     * in clubhouse story https://app.clubhouse.io/videoslots/story/65782/players-with-vault-balance-on-mrv
     * Its only used when we want to migrate a player to the older booster.
     *
     * Transfer cash from vault to the user balance and remove the booster_vault setting (self::$vault_key)
     * moving it to the old booster.
     *
     * @param DBUser $user
     * @return bool - false if there was an error, true on success.
     */
    public function removePlayerFromBoosterVault($user = null)
    {
        $user = cu($user);
        if (empty($user)) {
            return false;
        }

        $vault_amount = (int)$this->getVaultBalance($user);

        if ($vault_amount <= 0) {
            $user->deleteSetting(self::$vault_key);
            return true;
        }

        $result = $this->casino->changeBalance($user, $vault_amount, 'Transfer from Booster Vault', 101, false);
        if ($result === false) {
            return false;
        }

        $user->deleteSetting(self::$vault_key);
        phive('UserHandler')->logAction($user, "Booster vault {$vault_amount} cents transferred to balance", 'booster-vault', true);
        phive()->dumpTbl('booster-release-and-remove', ['amount' => $vault_amount], $user);

        return true;
    }

    /**
     * Automatic release for the "Vault" weekend booster.
     * Will credit the user for the amount of money they haven't manually claimed yet from the previous week.
     *
     * SQL EXPLANATION: 
     * (values from users_daily_booster_stats table are the sum of daily cached values from cash_transactions.
     * code to insert data to this users_daily_booster_stats table is under app\Repositories\UsersDailyBoosterStatsRepository.php in admin2)
     * current_vault_amount                 = user's current vault value(users_settings table setting =  'booster_vault')
     * week_total_generated_vault_amount    = sum of the week's(monday to NOW) all generated_boosters(how much the player earned in the vault during the week)
     * week_already_total_released_amount   = sum of the week's(monday to NOW) released_boostes(how much the player has already released from the vault during the week)
     * 
     * current_week_total_vault_amount         = sum of all cash_transactions of type 100 from the current week (monday to NOW). (this subquery is optional, and currently commented out for perfomance reason, but gives us a clear idea of the whole scenario when running the query manually on the DB for debug purposes)
     * current_week_already_released_amount    = sum of all cash_transactions of type 101 from the current week (we need to be sure that the player didn't release between monday/NOW, to avoid crediting more balance, currently commented out)
     * ---
     * This query will return only users who currently have some balance in the "booster_vault" (WHERE)
     * and who have money not transferred from last week (HAVING)
     *
     * NOTE: Not all the money inside the vault are released as when the player do the same manually (or an admin trigger this for the player)
     *
     * @param $country_where - single country = 'XX' or array NOT IN ('xx','yy',...)
     * @param $user_id - enforce this on a single user and return the value right away (not using queue)
     * @return bool - false if no country_where is passed.
     */
    public function autoRelaseBooster($country_where, $user_id = null) {

        if(empty($country_where)) {
            return false;
        }

        $user_where = '';
        if(!empty($user_id)) {
            $user_where = ' AND u.id = '.$user_id;
        }

        $last_week_friday = phive()->hisMod("last friday"); // Ex. '2023-11-03 00:00:00'
        $cur_week_monday = phive()->hisMod("last monday"); // Ex. '2023-11-10 00:00:00'
        $cur_week_now = phive()->hisNow();

        $sql = "
            SELECT
                us.user_id,
                us.value as current_vault_amount,
                SUM(udbs.generated_booster) AS week_total_generated_vault_amount,
                ABS(COALESCE(SUM(udbs.released_booster),0)) AS week_already_total_released_amount,
                /*We need realtime data on the current week (first query is optional but helpful on debug scenarios)*/
                /*COALESCE((SELECT ABS(SUM(amount)) FROM cash_transactions AS ct_current WHERE ct_current.user_id = us.user_id AND ct_current.transactiontype = 100 AND ct_current.timestamp BETWEEN '{$cur_week_monday}' AND '{$cur_week_now}'),0) AS current_week_total_vault_amount,*/
                /*COALESCE((SELECT SUM(ct_current.amount) FROM cash_transactions AS ct_current WHERE ct_current.user_id = us.user_id AND ct_current.transactiontype = 101 AND ct_current.timestamp BETWEEN '{$cur_week_monday}' AND '{$cur_week_now}'),0) AS current_week_already_released_amount,*/
                SUM(udbs.generated_booster) - ABS(COALESCE(SUM(udbs.released_booster),0)) AS week_to_release_amount
            FROM users_settings us
                INNER JOIN users u ON u.id = us.user_id AND u.country {$country_where} {$user_where}
                INNER JOIN users_daily_booster_stats udbs
                    ON udbs.user_id = us.user_id
                    AND udbs.date BETWEEN '{$last_week_friday}' AND '{$cur_week_now}'
                LEFT JOIN currencies c ON c.code = udbs.currency
            WHERE us.setting = '".self::$vault_key."' AND ROUND(us.value/c.multiplier) > 50
            GROUP BY udbs.user_id
            HAVING week_total_generated_vault_amount > (week_already_total_released_amount)
        ";

        $users_with_unreleased_booster_vault_from_previous_week = phive('SQL')->shs()->loadArray($sql);

        // when called for a single user we return the value right away (Ex. used to add the booster_vault balance to old booster notification)
        if(!empty($user_id)) {
            foreach($users_with_unreleased_booster_vault_from_previous_week as $user) {
                if ($user_id == $user['user_id']) {
                    return $this->releaseBoosterVault($user_id, true, $user['week_to_release_amount'], true, null, $sql, true);
                }
            }
            return false;
        }

        /** @var User[] $users */
        $users = [];
        // todo: convert this into a single SQL query considering the limit of WHERE IN to get the data from users table
        foreach($users_with_unreleased_booster_vault_from_previous_week as $user) {
            $users[$user['user_id']] = cu($user['user_id']);
        }
        $allowed_users = phive('MailHandler2')->filterMarketingBlockedUsers(array_values($users), true);
        $to_release = [];
        foreach($users_with_unreleased_booster_vault_from_previous_week as $user) {
            $to_release[] = [$user['user_id'], true, $user['week_to_release_amount'], false, !in_array($user['user_id'], $allowed_users)];
        }

        if (!empty($to_release)) {
            phive('Site/Publisher')->bulk('booster-vault', 'DBUserHandler/Booster', 'releaseBoosterVault', $to_release);
        }

        return true;
    }

    /**
     * Logic took from CasinoCashier "payQdTransactions()"
     * Run the auto release logic only for the country planned in the scheduled at a certain hour
     *
     * @param null $country - to enforce a specific country
     */
    public function scheduledAutoRelease($country = null) {
        list($scheduled, $not_scheduled) = phive('CasinoCashier')->getScheduledCountries(CasinoCashier::SCHEDULE_QUEUED_TRANSACTIONS);
        $cur_country = empty($country) ? $scheduled : $country;
        if(!empty($cur_country)){
            if($cur_country == 'NA'){
                $in_countries = phive('SQL')->makeIn(array_values($not_scheduled));
                $this->autoRelaseBooster("NOT IN($in_countries)");
            }else{
                $this->autoRelaseBooster("= '$cur_country'");
            }
        }
    }


    /**
     * @param null|DBUser $user
     * @return bool|null
     */
    public function optedOutToVault($user = null)
    {
        return $this->optVaultCommon('check_opted_out', $user);
    }

    /**
     * @param null|DBUser $user
     * @return bool|null
     */
    public function optInToVault($user = null)
    {
        return $this->optVaultCommon('in', $user);
    }

    /**
     * @param null|DBUser $user
     * @return bool|null
     */
    public function optOutToVault($user = null)
    {
        return $this->optVaultCommon('out', $user);
    }

    /**
     * @param string $action
     * @param null|DBUser $user
     * @return bool|null
     */
    private function optVaultCommon($action, $user = null)
    {
        $user = cu($user);
        if (empty($user)) {
            return null;
        }
        switch ($action) {
            case 'check_opted_out':
                return $user->hasSetting(self::$opt_out_key);
                break;
            case 'in':
                return $user->deleteSetting(self::$opt_out_key);
                break;
            case 'out':
                return $user->setSetting(self::$opt_out_key, 1);
                break;
            default:
                return null;
        }
    }

    /**
     * Returns the list of all the wins for a specific player on a single day
     * with how much that contributed to the new booster
     *
     * @param int $uid
     * @param $date Y-m-d
     * @return array|bool|mixed|string|null
     */
    public function getWinsWithBoostedAmount($uid, $date, $page = 1, $page_size = 20) {
        if(empty($page) || !is_numeric($page) || $page < 1) { // to prevent fiddling with GET param
            $page = 1;
        }
        $offset = ($page - 1) * $page_size;

        $sql = "
            SELECT 
                wins.id,
                wins.amount, 
                wins.created_at, 
                wins.currency, 
                ct.amount as transferred_to_vault,
                mg.game_name 
            FROM 
                wins
            LEFT JOIN
	            micro_games mg ON mg.ext_game_name = wins.game_ref -- left join to avoid scenario with missing game name 
            LEFT JOIN 
                cash_transactions ct 
                ON wins.id = ct.parent_id
                    AND ct.transactiontype = 100 
                    AND ct.user_id = $uid 
            WHERE 
                wins.user_id = $uid
                AND created_at like '$date%'
            GROUP BY wins.id
            ORDER BY created_at ASC";
        $result = $this->db->sh($uid)->loadArray($sql);
        // I slice via PHP to avoid doing a second query to grab the total count for the paginator.
        $count = count($result);
        $result = array_slice($result, $offset, $page_size);
        return [$result, $count];
    }

    /**
     * Return for the request year|month|week combination the amount of weekend booster (vault) the player earned.
     * In case of year data is aggregated by month.
     *
     * @param $uid
     * @param null $year
     * @param null $month
     * @param null $week
     * @return array|mixed|string
     */
    public function getAggreatedWinsFromCashTransactions($uid, $year = null, $month = null, $week = null) {
        $year = (int)$year;
        $month = (int)$month;
        $week = (int)$week;

        $group_by = ' GROUP BY month';
        if(empty($year)) {
            $year = date('Y');
        }
        $year_where = "AND YEAR(date) = '$year'";

        $month_where = '';
        if(!empty($month)) {
            $month_where = " AND MONTH(date) = '$month'";
            $group_by = ' GROUP BY day';
        }
        // week is only used in admin2, we need to remove filtering by year/month
        // to prevent excluding days in a week spanning across 2 months/years Ex. 28-29-30-31-1-2-3
        $week_where = '';
        if(!empty($week)) {
            $year_where = "";
            $month_where = '';
            $week = str_pad($week, 2, '0', STR_PAD_LEFT);
            list($week_start, $week_end) = phive()->getWeekStartEnd("{$year}W{$week}");
            $week_where = " AND date BETWEEN '$week_start' AND '$week_end'";
            $group_by = ' GROUP BY day';
        }

        $sql = "
            SELECT 
                currency, 
                SUM(generated_booster) as transferred_to_vault,
                date,
                YEAR(date) as year,
                MONTH(date) as month,
                DAY(date) as day
            FROM 
                users_daily_booster_stats 
            WHERE 
                user_id = {$uid}
                $year_where
                $month_where
                $week_where
            $group_by
            ORDER BY 
                date ASC
        ";

        $res = phQget($sql);
        if(!empty($res)) {
            return $res;
        }

        $res = $this->db->sh($uid)->loadArray($sql);

        phQset($sql, $res, 7200);

        return $res;
    }

    /**
     * Return the sum of all the "$vault_key" from the user settings converted into EUR.
     *
     * @param bool $change
     * @param string $currency
     * @return bool
     */
    public function getTotalVaultBalance()
    {
        $str = "SELECT IFNULL(SUM(us.value / c.multiplier), 0) AS total_booster_vault 
                    FROM users_settings us
                      INNER JOIN users AS u ON u.id = us.user_id
                      INNER JOIN currencies AS c ON u.currency = c.code 
                    WHERE us.setting = '". self::$vault_key. "';";

        return current(phive('SQL')->shs('sum')->loadArray($str)[0]);
    }
}
