<?php

/**
 * Created by PhpStorm.
 * User: pezo
 * Date: 2015.11.17.
 * Time: 9:29
 */

namespace App\Controllers;

use App\Classes\DateRange;
use App\Helpers\DataFormatHelper;
use App\Helpers\DateHelper;
use App\Helpers\PaginationHelper;
use App\Models\Bet;
use App\Models\User;
use App\Models\UserDailyGameStatistics;
use App\Models\UserDailyStatistics;
use App\Models\Win;
use App\Repositories\BetsAndWinsRepository;
use App\Repositories\GameRepository;
use Carbon\Carbon;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\ReplicaFManager as ReplicaDB;
use Illuminate\Database\Capsule\Manager as DBM;
use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class BetsAndWinsController implements ControllerProviderInterface
{
    const GAME_HISTORY_PAGE_LENGTH = 10;

    public function connect(Application $app)
    {
        $factory = $app['controllers_factory'];
        return $factory;
    }

    /**
     * Filtering users and paginate the results
     *
     * @param Application $app
     * @param Request $request
     * @return mixed
     */
    public function gameHistory(Application $app, Request $request, User $user)
    {
        $is_initial = !$request->isXmlHttpRequest();

        if ($is_initial || $request->get('source') == 'bets') {
            $bets = DB::shTable($user->getKey(), 'bets')
                ->select('created_at', 'amount', 'game_ref', 'currency', 'micro_games.game_name as game_name')
                ->where('user_id', $user->getKey())
                ->leftJoin('micro_games', function ($join) {
                    $join->on('bets.game_ref', '=', 'micro_games.ext_game_name');
                    $join->on('bets.device_type', '=', 'micro_games.device_type_num');
                });
            $paginator_bets = new PaginationHelper($bets, $request, ['length' => self::GAME_HISTORY_PAGE_LENGTH, 'order' => ['column' => 'created_at', 'order' => 'DESC']]);
            $bets = $paginator_bets->getPage($is_initial);
            $data = &$bets;
        }

        if ($is_initial || $request->get('source') == 'wins') {
            $wins = DB::shTable($user->getKey(), 'wins')
                ->select('created_at', 'amount', 'game_ref', 'currency', 'micro_games.game_name as game_name')
                ->where('user_id', $user->getKey())
                ->leftJoin('micro_games', function ($join) {
                    $join->on('wins.game_ref', '=', 'micro_games.ext_game_name');
                    $join->on('wins.device_type', '=', 'micro_games.device_type_num');
                });
            $paginator_wins = new PaginationHelper($wins, $request, ['length' => self::GAME_HISTORY_PAGE_LENGTH, 'order' => ['column' => 'created_at', 'order' => 'DESC']]);
            $wins = $paginator_wins->getPage($is_initial);
            $data = &$wins;
        }

        if ($is_initial) {
            $max_records_total = max($bets['recordsTotal'], $wins['recordsTotal']);
            $page_length = self::GAME_HISTORY_PAGE_LENGTH;

            $bets = $bets['data'];
            $wins = $wins['data'];

            return $app['blade']->view()->make('admin.user.betsandwins.history', compact('app', 'user', 'bets', 'wins', 'max_records_total', 'page_length'))->render();
        } else {

            $data['recordsFiltered'] = $data['recordsTotal'] = $request->get('max_records_total');
            return $app->json($data);
        }
    }

    public function getGamesSelection(Application $app, Request $request)
    {
        $search_value = $request->get('search');
        return $app->json(GameRepository::getGameSelectList($search_value));
    }

    public function index(Application $app, Request $request, User $user)
    {
        if ($request->isXmlHttpRequest()) {
            return $this->getGamesSelection($app, $request);
        }
        return $app['blade']->view()->make('admin.user.betsandwins.list-index', compact('app', 'user'))->render();
    }

    private function getBetListQuery(Application $app, User $user, Request $request, bool $archived = false)
    {
        $profile_repo = new BetsAndWinsRepository($app, $user, $request);
        return $profile_repo->getBetListQuery($archived);
    }

    private function getWinListQuery(Application $app, User $user, Request $request, bool $archived = false)
    {
        $profile_repo = new BetsAndWinsRepository($app, $user, $request);
        return $profile_repo->getWinListQuery($archived);
    }

    private function getBetWinListQuery(Application $app, User $user, Request $request, bool $archived = false)
    {
        $profile_repo = new BetsAndWinsRepository($app, $user, $request);
        return $profile_repo->getBetWinListQuery($archived);
    }

    public function listAll(Application $app, Request $request, User $user)
    {
        if ($request->isXmlHttpRequest()) {
            return $this->getGamesSelection($app, $request);
        }
        $route = $request->getUriForPath($request->getRequestUri());
        $profile_repo = new BetsAndWinsRepository($app, $user, $request);
        $poolX_bet_service = $app['poolx.bet_service'];
        $altenar_bet_service = $app['altenar.bet_service'];
        $vertical = $request->get('vertical');
        $bets_query = $this->getBetListQuery($app, $user, $request);
        $wins_query = $this->getWinListQuery($app, $user, $request);
        $bets_and_wins_query = $this->getBetWinListQuery($app, $user, $request);
        $archive_enabled = $app['vs.config']['archive.db.support'];

        if ($archive_enabled) {
            try {
                $archived_bets_query = $this->getBetListQuery($app, $user, $request, $archive_enabled);
                $archived_wins_query = $this->getWinListQuery($app, $user, $request, $archive_enabled);
                $archived_bets_and_wins_query = $this->getBetWinListQuery($app, $user, $request, $archive_enabled);
            } catch (\Exception $e) {
                $app['monolog']->addError("[BO-ARCHIVE] Get archive failed: {$e->getMessage()}");
                $archived_bets_query = $archived_wins_query = $archived_bets_and_wins_query = 0;
            }
        }
        $transactions_query = $profile_repo->getTransactionsListQuery();

        if (!empty($request->get('export'))) {
            if (!p('view.account.betswins.download.csv')) {
                $app->abort('403');
            }

            if($request->get('vertical_export') === 'sportsbook') {
                return $profile_repo->exportSportsbookBetList($profile_repo->getSportsbookBets());
            }

            if ($request->get('vertical_export') === 'poolx') {
                return $poolX_bet_service->exportPoolXBets(
                    $user,
                    $request,
                    $request->get('start_date'),
                    $request->get('end_date')
                );
            }

            if ($request->get('vertical_export') === 'altenar') {
                return $altenar_bet_service->exportAltenarBets(
                    $user,
                    $request,
                    $request->get('start_date'),
                    $request->get('end_date')
                );
            }

            return $profile_repo->exportBetWinList($bets_and_wins_query->get());
        } else {
            $query_data = $profile_repo->query_data;

            if($vertical === 'all' || $vertical === 'casino') {
                if (empty($request->get('chrono'))) {
                    $bets = $profile_repo->mergeArchived($bets_query, $archive_enabled ? $archived_bets_query : 0);
                    $wins = $profile_repo->mergeArchived($wins_query, $archive_enabled ? $archived_wins_query : 0);
                } else {
                    $bets_and_wins = $profile_repo->mergeArchived($bets_and_wins_query, $archive_enabled ? $archived_bets_and_wins_query : 0);
                }

                $transactions = $transactions_query->get();
            }

            if($vertical === 'all' || $vertical === 'sportsbook') {
                $sportsbook_bets = $profile_repo->getSportsbookBets();
                $altenar_bets = $altenar_bet_service->getAltenarBets($request, $user);
            }

            if($vertical === 'all' || $vertical === 'poolx') {
                $poolX_bets = $poolX_bet_service->getPoolXBets($request, $user);
            }

            $view_name = $request->get('mp') == 1 ? 'battles.betsandwins' : 'betsandwins.list-all';
            $form_url = $request->get('mp') == 1 ? 'admin.user-battle-betsandwins-all' : 'admin.user-betsandwins-all';
            return $app['blade']->view()->make("admin.user.$view_name", compact('app', 'user', 'bets', 'wins', 'bets_and_wins', 'sportsbook_bets', 'poolX_bets', 'altenar_bets', 'query_data', 'transactions', 'form_url', 'route'))->render();
        }
    }

    /**
     * Get sportsbook bet details for a specific bet
     * @param Application $app
     * @param Request $request
     * @param User $user
     * @param int $bet_id
     * @return JsonResponse
     */
    public function getSportsbookBetDetails(Application $app, Request $request, User $user, int $bet_id)
    {
        $profile_repo = new BetsAndWinsRepository($app, $user, $request);
        $bet_detail = $profile_repo->getSportsbookBetDetails($bet_id);
        return $app->json($bet_detail);
    }

    public function getSportsbookSettlementBetDetails(Application $app, User $user, int $betId): JsonResponse
    {
        // Get the latest sport transaction for the user and bet ID
        $latestSportTransaction = $app['sportsbook.repository']->getLatestSportTransaction($user->getKey(), $betId);

        // Extract relevant information from the latest sport transaction
        $betResult = $latestSportTransaction->bet_type;
        $isSettled = !is_null($latestSportTransaction->settled_at);

        $isPermitted = p('admin.sportsbook.manual-ticket-settle');

        // Determine whether the bet can be reopened
        $canReopen = $isSettled && $betResult === $app['sportsbook_manual_bet_settlement_service']::BET && $isPermitted;

        // Determine whether the bet can be settled
        $canSettle = !$isSettled && $betResult === $app['sportsbook_manual_bet_settlement_service']::BET && $isPermitted;

        return new JsonResponse([
            'bet_id' => $betId,
            'bet_result' => $betResult,
            'is_settled' => $isSettled,
            'can_reopen' => $canReopen,
            'can_settle' => $canSettle
        ]);
    }


    // TODO seems legacy stuff and the page doesn't load anymore - everything shuold now be grouped under ListAll. can this be removed? if yes remove from UserProfileController and remove /betsandwins/partials/filter (replaced by filter-all)
    public function listBets(Application $app, Request $request, User $user)
    {
        $profile_repo = new BetsAndWinsRepository($app, $user, $request);
        $bets_query = $profile_repo->getBetListQuery(false, true);

        if (!is_null($request->get('export'))) {
            if (!p('view.account.betswins.download.csv')) {
                $app->abort('403');
            }
            return $profile_repo->exportBetList($bets_query->get());
        } else {
            $query_data = $profile_repo->query_data;
            $bets = $bets_query->paginate(100, ['*'], 'page', $page = $request->get('page', 1))
                ->appends($query_data)
                ->setPath($app['url_generator']->generate('admin.user-betsandwins-bets', ['user' => $user->id]));

            return $app['blade']->view()->make('admin.user.betsandwins.list-bets', compact('app', 'user', 'bets', 'query_data'))->render();
        }
    }

    function calculateXp(&$uobj, $bet_amount, $currency, &$cur_game){
        $xp_multi = max($uobj->getSetting('xp-multiply'), 1);
        $amount   = mc(($bet_amount / 100) * $xp_multi, $currency, 'div', false);
        return phive('Casino')->getRtpProgress($amount, $cur_game);
    }

    public function xpProgress(Application $app, Request $request, User $user)
    {
        if ($request->isXmlHttpRequest()) {
            foreach ($request->get('form') as $form_elem) {
                $request->request->set($form_elem['name'], $form_elem['value']);
            }
        }

        $date_range = DateRange::rangeFromRequest($request, DateRange::DEFAULT_TODAY);
        $request->request->set('custom_order', true);
        $request->request->set('ext_start_date', $s = $date_range->getStart('timestamp'));
        $request->request->set('ext_end_date', $e = $date_range->getEnd('timestamp'));

        $user_object = cu($user->id);
        $total_xp_points = DataFormatHelper::nf(phive('Tournament')->getDepWagerLimSums($user_object, $s, $e)['wager_sum']);
        $calculate_xp = function ($bet) use ($user_object) {
            $bet->xp = $this->calculateXp($user_object,$bet->amount, $bet->currency, phive('MicroGames')->getById($bet->g_id));
            return $bet;
        };

        $profile_repo = new BetsAndWinsRepository($app, $user, $request);
        $bets = $profile_repo->getBetListQuery();

        $bets->whereIn('mg1.tag', phive('Casino')->getSlotsType());

        if (!is_null($request->get('export'))) {
            if (!p('view.account.xp-history.download.csv')) {
                $app->abort('403');
            }
            return $profile_repo->exportBetListXp(array_map($calculate_xp, $bets->get()->toArray()));
        } else {
            $query_data = $profile_repo->query_data;
            $form_url = 'admin.user-xp-history';
            $query_data['skip_order_by'] = true;

            if ($request->isXmlHttpRequest()) {
                $bets = new PaginationHelper($bets, $request, ['length' => 25, 'order' => ['column' => 'created_at', 'order' => 'DESC']]);
                $bets = $bets->getPage(false);
                $bets['data'] = array_map($calculate_xp, $bets['data']);

                return $app->json($bets);
            }
            return $app['blade']->view()->make('admin.user.betsandwins.xp-history', compact('app', 'user', 'query_data', 'form_url', 'date_range', 'total_xp_points'))->render();
        }
    }

    // TODO seems legacy stuff and the page doesn't load anymore - everything shuold now be grouped under ListAll. can this be removed? if yes remove from UserProfileController and remove /betsandwins/partials/filter (replaced by filter-all)
    public function listWins(Application $app, Request $request, User $user)
    {
        $profile_repo = new BetsAndWinsRepository($app, $user, $request);
        $wins_query = $profile_repo->getWinListQuery();

        if (!is_null($request->get('export'))) {
            if (!p('view.account.betswins.download.csv')) {
                $app->abort('403');
            }
            return $profile_repo->exportWinList($wins_query->get());
        } else {
            $query_data = $profile_repo->query_data;
            $wins = $wins_query->paginate(100, ['*'], 'page', $page = $request->get('page', 1))
                ->setPath($app['url_generator']->generate('admin.user-betsandwins-wins', ['user' => $user->id]))
                ->appends($query_data);

            return $app['blade']->view()->make('admin.user.betsandwins.list-wins', compact('app', 'user', 'wins', 'query_data'))->render();
        }
    }

    // TODO seems legacy stuff and the page doesn't load anymore - everything shuold now be grouped under ListAll. can this be removed? if yes remove from UserProfileController and remove /betsandwins/partials/filter (replaced by filter-all)
    public function listTransactions(Application $app, Request $request, User $user)
    {
        $profile_repo = new BetsAndWinsRepository($app, $user, $request);
        $transactions_query = $profile_repo->getTransactionsListQuery();

        if (!is_null($request->get('export'))) {
            if (!p('view.account.betswins.download.csv')) {
                $app->abort('403');
            }
            return $profile_repo->exportTransactionList($transactions_query->get());
        } else {
            $query_data = $profile_repo->query_data;
            $transactions = $transactions_query->paginate(100, ['*'], 'page', $page = $request->get('page', 1))
                ->setPath($app['url_generator']->generate('admin.user-betsandwins-transactions', ['user' => $user->id]))
                ->appends($query_data);

            return $app['blade']->view()->make('admin.user.betsandwins.list-transactions', compact('app', 'user', 'transactions', 'query_data'))->render();
        }
    }

    /**
     * Get user daily statistics
     *
     * @param Application $app
     * @param Request $request
     * @param User $user
     * @return string
     */
    public function gameStatistics(Application $app, Request $request, User $user)
    {
        $date_range = DateHelper::validateDateRange($request, 3);

        $game_statistics_query = UserDailyGameStatistics::selectRaw('SUM( users_daily_game_stats.bets ) AS bets')
            ->selectRaw('SUM( users_daily_game_stats.wins ) AS wins')
            ->selectRaw('SUM( users_daily_game_stats.frb_wins ) AS frb_wins')
            ->selectRaw('SUM( users_daily_game_stats.op_fee ) AS op_fee')
            ->selectRaw('SUM( users_daily_game_stats.jp_contrib ) AS jp_contrib')
            ->selectRaw('SUM( users_daily_game_stats.jp_fee ) AS jp_fee')
            ->selectRaw('SUM( users_daily_game_stats.frb_ded ) AS frb_ded')
            ->selectRaw('SUM( users_daily_game_stats.bets) - SUM(users_daily_game_stats.wins) AS gross')
            ->selectRaw('micro_games.game_name')
            ->selectRaw('users_daily_game_stats.game_ref')
            ->selectRaw('users_daily_game_stats.date')
            ->selectRaw('CASE WHEN users_daily_game_stats.device_type = 1 THEN "HTML5" ELSE "Flash" END as device_type')
            ->selectRaw('SUM(users_daily_game_stats.bets) - SUM(users_daily_game_stats.wins) - SUM(users_daily_game_stats.jp_contrib) - SUM(users_daily_game_stats.frb_ded) AS overall_gross')
            ->selectRaw('SUM(users_daily_game_stats.bets) - SUM(users_daily_game_stats.wins) - SUM(users_daily_game_stats.jp_fee) - SUM(users_daily_game_stats.frb_wins) - SUM(users_daily_game_stats.jp_contrib) - SUM(users_daily_game_stats.op_fee) AS site_gross')
            ->leftJoin('users', 'users_daily_game_stats.user_id', '=', 'users.id')
            ->leftJoin('micro_games', function ($join) {
                $join->on('users_daily_game_stats.game_ref', '=', 'micro_games.ext_game_name');
                $join->on('users_daily_game_stats.device_type', '=', 'micro_games.device_type_num');
            })
            ->where('users_daily_game_stats.user_id', $user->getKey())
            ->groupBy(['users_daily_game_stats.game_ref', 'users_daily_game_stats.device_type'])
            ->orderBy('users_daily_game_stats.game_ref');

        if (is_null($date_range['end_date'])) {
            $game_statistics_query->where('users_daily_game_stats.date', '>=', $date_range['start_date']);
        } else {
            $game_statistics_query->whereBetween('users_daily_game_stats.date', [$date_range['start_date'], $date_range['end_date']]);
        }

        $game_statistics = $game_statistics_query->get();
        $sort = ['column' => 0, 'type' => "asc", 'start_date' => $date_range['start_date'], 'end_date' => $date_range['end_date']];

        return $app['blade']->view()->make('admin.user.gamestatistics.index', compact('app', 'user', 'game_statistics', 'sort'))->render();
    }

    /**
     * Get user game history
     *
     * @param Application $app
     * @param Request $request
     * @param User $user
     */
    public function gameHistoryOLD(Application $app, Request $request, User $user)
    {
        $bets = Bet::selectRaw('DISTINCT bets.amount AS amount')
            ->selectRaw('bets.created_at')
            ->selectRaw('bets.currency')
            ->selectRaw('micro_games.game_name')
            ->leftJoin('micro_games', function ($join) {
                $join->on('bets.game_ref', '=', 'micro_games.ext_game_name');
                $join->on('bets.device_type', '=', 'micro_games.device_type_num');
            })
            ->where('bets.user_id', $user->getKey())
            ->orderBy('bets.created_at', 'DESC')
            ->limit(100)->get();

        $wins = Win::selectRaw('DISTINCT wins.amount AS amount')
            ->selectRaw('wins.created_at')
            ->selectRaw('wins.currency')
            ->selectRaw('micro_games.game_name')
            ->leftJoin('micro_games', function ($join) {
                $join->on('wins.game_ref', '=', 'micro_games.ext_game_name');
                $join->on('wins.device_type', '=', 'micro_games.device_type_num');
            })
            ->where('wins.user_id', $user->getKey())
            ->orderBy('wins.created_at', 'DESC')
            ->limit(100)->get();

        return $app['blade']->view()->make('admin.user.betsandwins.gamehistory', compact('app', 'user', 'bets', 'wins'))->render();
    }

    /**
     * User casino CashBack history
     * - Default - will rely on users_daily_stats (gen_loyalty)
     * - if doBoosterVault(cu($user)) == true (SE for now) - we merge the data from default with cash_transactions (100)
     *
     * @param Application $app
     * @param User $user
     * @param Request $request
     * @return mixed
     */
    public function casinoCashBack(Application $app, User $user, Request $request)
    {
        $date_range['year'] = $request->get('year');
        $date_range['month'] = $request->get('month');
        $date_range['week'] = $request->get('week');
        // cannot use default on "get()" cause empty string is a valid value for "get()"
        if(empty($date_range['year'])) {
            $date_range['year'] = Carbon::now()->format('Y');
        }
        $res = [
            'list' => [],
            'type' => '',
            'total' => 0
        ];

        $casino_cash_back_query = UserDailyStatistics::on(replicaDatabaseSwitcher(true))->selectRaw('WEEKOFYEAR(users_daily_stats.date) AS week')
            ->selectRaw('MONTH(users_daily_stats.date) AS month')
            ->selectRaw('DAY(users_daily_stats.date) AS day')
            ->selectRaw('YEAR(users_daily_stats.date) AS year')
            ->selectRaw('SUM(users_daily_stats.gen_loyalty) AS generated_loyalty')
            ->selectRaw('date AS date')
            ->where('users_daily_stats.user_id', '=', $user->id)
            ->whereRaw('YEAR(users_daily_stats.date) = ?', [$date_range['year']])
            ->orderBy('users_daily_stats.date', 'ASC');

        if (empty($date_range['month']) && empty($date_range['week'])) { // year
            $res['type'] = 'year';
            $group_by = 'month';
            $list_key = 'month';
            $range_size = 12;
        } elseif (!empty($date_range['month']) && empty($date_range['week'])) { // month
            $res['type'] = 'month';
            $group_by = 'day';
            $list_key = 'day';
            $range_size = Carbon::create($date_range['year'], $date_range['month'])->daysInMonth;

            $casino_cash_back_query->whereRaw('MONTH(users_daily_stats.date) = ?', [$date_range['month']]);
        } else { // week
            $res['type'] = 'week';
            $group_by = 'day';
            $list_key = 'date';
            $range_size = 6; // first day is added here below.

            $casino_cash_back_query->whereRaw('WEEKOFYEAR(users_daily_stats.date) = ?', [$date_range['week']]);

            $first_day_of_week = Carbon::now();
            $first_day_of_week->setISODate($date_range['year'], $date_range['week']);
            $first_day_of_week->startOfWeek();

            // adding first day to list
            $res['list'][$first_day_of_week->format('Y-m-d')] = ['amount'  => 0];
        }
        for ($i = 1; $i <= $range_size; $i++) {
            $index = $res['type'] === 'week' ? $first_day_of_week->addDay()->format('Y-m-d') : $i;
            $res['list'][$index] = ['amount'  => 0];
        }

        $casino_cash_back = $casino_cash_back_query->groupBy($group_by)->get();
        foreach ($casino_cash_back as $cb) {
            $res['list'][$cb->{$list_key}]['amount'] = $cb->generated_loyalty;
            $res['total'] += $cb->generated_loyalty;
        }

        // When the user country has the new booster we merge the data from the new booster (from cash_transactions) to the old one.
        if(phive('DBUserHandler/Booster')->doBoosterVault(cu($user->getKey())) === true) {
            $stats_from_cash_transaction = phive('DBUserHandler/Booster')->getAggreatedWinsFromCashTransactions($user->id, $date_range['year'], $date_range['month'], $date_range['week']);
            foreach($stats_from_cash_transaction as $stat) {
                $res['list'][$stat[$list_key]]['amount'] += abs($stat['transferred_to_vault']);
                $res['total'] += abs($stat['transferred_to_vault']);
            }
        }

        return $app['blade']->view()->make('admin.user.gamestatistics.casinocashback', compact('app', 'user', 'res', 'date_range'))->render();
    }


    public function casinoRaces(Application $app, User $user, Request $request)
    {
        $date_range = DateHelper::validateDateRange($request);
        $query_parameters = ['user_id' => $user->getKey(), 'start_time' => Carbon::parse($date_range['start_date'])->format('Y-m-d 00:00:00')];
        $user_id = $query_parameters['user_id'];
        $start_time = $query_parameters['start_time'];
        $end_date_sql = ReplicaDB::raw("AND re.end_time < :end_time");

        if (!is_null($date_range['end_date'])) {
            $query_parameters['end_time'] = Carbon::parse($date_range['end_date'])->format('Y-m-d 23:59:59');
        }

        $casinoRaces = ReplicaDB::shSelect($user_id,'race_entries', "SELECT re.start_time as start_time,
                              re.end_time as end_time,
                              re.race_balance as spins,
                              re.spot as position,
                              re.prize as prize
                            FROM race_entries re
                            WHERE re.user_id = :user_id
                            AND re.start_time > :start_time
                            $end_date_sql
                            ORDER BY re.end_time DESC",[
            'user_id' => $query_parameters['user_id'],
            'start_time' => $query_parameters['start_time'],
            'end_time' => isset($query_parameters['end_time'])? $query_parameters['end_time'] : Carbon::now()->toDateTimeString()
        ]);

        $sort = ['column' => 0, 'type' => "desc", 'start_date' => $date_range['start_date'], 'end_date' => $date_range['end_date']];
        return $app['blade']->view()->make('admin.user.gamestatistics.casinoraces', compact('app', 'user', 'casinoRaces', 'sort'))->render();
    }

}
