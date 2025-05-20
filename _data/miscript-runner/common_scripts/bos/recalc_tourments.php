<?php

/**
 * This function will recalculate the battle that all players with result_place 0.
 *
 * @param integer $tournamentID    $Tournament ID

 */
function Recalc_Tournament_All_Shards($tournamentID)
{
    echo "Doing: Leaderboard for missing users \n";
    $sql = phive('SQL');
    $query = "SELECT *
            FROM tournaments
            WHERE id = {$tournamentID};";

    $all_tournaments = $sql->loadArray($query);

    foreach ($all_tournaments as $tournament) {
        phMdel('prizes-calculated' . $tournament['id']);
        $res = phive('Tournament')->calcPrizes($tournament, $skip_check = true);
        echo "Calculated for tournament id: {$tournament['id']} \n ";
    }
    echo "Leaderboard recalculated and prizes credited \n";
}

/**
 * This function will recalculate the battle for all users with result_place 0 on a specific shard.
 *
 * @param integer $tournamentID    $tournament ID
 * @param integer $shard         Shard number
 */
function Recalc_Tournament_Specific_Shard($tournamentID, $shard)
{
    $sql = phive('SQL');

    echo "Doing: Leaderboard for missing users \n";

    $query = "SELECT *
            FROM tournaments
            WHERE id = {$tournamentID};";

    $tournament_list = $sql->loadArray($query);

    foreach ($tournament_list as $t){

        $sql->sh(1)->updateArray('tournament_entries',
            ['status' => "finished" ],
            ['t_id' => $t['id'], 'status' => "open"]);

        phive('Tournament')->ladder = [];

        $t = is_numeric($t) ? phive('Tournament')->byId($t) : $t;

        $skip_check=true;
        if(!$skip_check && !empty($t['prizes_calculated']))
            return;
        $t['prizes_calculated'] = 1;
        phive('Tournament')->save($t);
        $tpl                    = phive('Tournament')->getParent($t);
        $users                  = array();
        $entries                = phive('Tournament')->sortLeaderboard($t, phive('Tournament')->entries($t));

        foreach($entries as $e)
            $users[$e['id']] = cu($e['user_id']);

        if (!empty($tpl['award_ladder_tag'])) {
            $res = array();

            foreach ($entries as $i => $e) {
                if (!phive('Tournament')->canGetPrize($e, $t))
                    continue;

                $u = $users[$e['id']];
                $ud = $u->data;

                phive('Tournament')->wsCalcPrizeFinished($ud, $t, $e);

                $place = $i + 1;
                phive('Tournament')->winEvent($t, $place, $ud);
                $a = phive('Tournament')->getPrizeAward($t, $place, $e['user_id']);
                $res[] = $a;
                $won_str = empty($a) ? 'nothing' : $a['alias'];


                $user_val=str_split($u->userId);
                $lastDigit = end($user_val);
                $node= [$shard]; //can be modified with the require nodes

                if(in_array($lastDigit, $node)) {
                    phive('UserHandler')->logAction($u, "Won: $won_str, Position: $place, Battle name: {$t['tournament_name']}, Battle id: {$t['id']}", 'battle');
                    phive('Trophy')->giveAward($a, $ud);
                    $e['result_place'] = $place;
                    phive('Tournament')->saveEntry($e);
                    phive('Tournament')->returnCashBalance($u, $t, $e);
                }
            }
        }

        phive('Tournament')->save($t);
        phive('Tournament')->handleBountyPrize($t);

        phive('Tournament')->setPrizeAmount($entries, $t);

        if($t['category'] == 'added' && !empty($t['guaranteed_prize_amount'])){
            // Prize diff is the whole guaranteed as that is now regarded as the added amount.
            $prize_diff     = $t['guaranteed_prize_amount'];
            // The prize amount is now the whole total of guaranteed and buy ins.
            $prize_amount     = $t['guaranteed_prize_amount'] + $t['prize_amount'];
        }else if(!empty($t['guaranteed_prize_amount']) && $t['guaranteed_prize_amount'] > $t['prize_amount']){
            $prize_diff     = $t['guaranteed_prize_amount'] - $t['prize_amount'];
            $prize_amount     = $t['guaranteed_prize_amount'];
        }else
            $prize_amount     = $t['prize_amount'];

        foreach($entries as $i => $e){
            if(!phive('Tournament')->canGetPrize($e, $t))
                continue;
            $u = $users[$e['id']];
            $ud = $u->data;

            phive('Tournament')->wsCalcPrizeFinished($ud, $t, $e);

            $place         = $i + 1;
            phive('Tournament')->winEvent($t, $place, $u->data);
            $percent            = phive('Tournament')->getPrizePercent($t, $place);
            $prize            = floor($percent * $prize_amount);
            $prize_changed     = floor(phive('Tournament')->chgToUsr($u, $prize));
            $e['won_amount']   = $prize;

            $user_val=str_split($u->userId);
            $lastDigit = end($user_val);
            $node= [$shard]; //can be modified with the require nodes

            if(in_array($lastDigit, $node)) {
                phive('UserHandler')->logAction($u, "Won: $prize, Position: $place, Battle name: {$t['tournament_name']}, Battle id: {$t['id']}", 'battle');
                if ($t['prize_type'] == 'cash-fixed')
                    phive('Tournament')->returnCashBalance($u, $t, $e);
                $e['result_place'] = $place;
                if (!empty($prize))
                    phive('Tournament')->handleCashPrize($u, $prize_changed, $t, $prize_diff, $percent, $prize, $e);
                phive('Tournament')->saveEntry($e);
            }
        }

        phive('Tournament')->save($t);
        phive('Tournament')->handleBountyPrize($t);

        phive('Tournament')->initMem($t, false);
        echo "Leaderboard recalculated and prizes credited \n";
    }
}


/**
 * This function will recalculate the battle when calculation stopped in a particular place, then users from a particular result place are missing award
 * @param integer $touramentID    Tournament ID
 * @param integer $last_result_place    Let’s say tournament with id 3824939 was calculated until place 9, the $last_result_place should be 10.

 */
function Recalc_Tournament_From_Last_Place($tournamentID, $last_result_place)
{
    $sql = phive('SQL');
    echo "Doing: Leaderboard for missing users \n";
    $query = "SELECT *
            FROM tournaments
            WHERE id = {$tournamentID};";
    $tournament_list = $sql->loadArray($query);

    foreach ($tournament_list as $t) {
        phive('Tournament')->ladder = [];

        $t = is_numeric($t) ? phive('Tournament')->byId($t) : $t;

        $skip_check = false;
        echo "Calculating leaderboard for tournament {$t['id']} \n";

        $t['prizes_calculated'] = 1;

        phive('Tournament')->save($t);
        $tpl = phive('Tournament')->getParent($t);
        $users = array();
        $entries = phive('Tournament')->sortLeaderboard($t, phive('Tournament')->entries($t));

        foreach ($entries as $e)
            $users[$e['id']] = cu($e['user_id']);

        if (!empty($tpl['award_ladder_tag'])) {
            $res = array();

            foreach ($entries as $i => $e) {
                if (!phive('Tournament')->canGetPrize($e, $t))
                    continue;

                $u = $users[$e['id']];
                $ud = $u->data;
                phive('Tournament')->wsCalcPrizeFinished($ud, $t, $e);

                $place = $i + 1;

                phive('Tournament')->winEvent($t, $place, $ud);
                $a = phive('Tournament')->getPrizeAward($t, $place, $e['user_id']);
                $res[] = $a;
                $won_str = empty($a) ? 'nothing' : $a['alias'];

                $start_from = $last_result_place;

                if ($place >= $start_from) {
                    phive('UserHandler')->logAction($u, "Won: $won_str, Position: $place, Battle name: {$t['tournament_name']}, Battle id: {$t['id']}", 'battle');
                    phive('Trophy')->giveAward($a, $ud);
                    $e['result_place'] = $place;
                    phive('Tournament')->saveEntry($e);
                    phive('Tournament')->returnCashBalance($u, $t, $e);
                }
            }
            phive('Tournament')->save($t);
            phive('Tournament')->handleBountyPrize($t);
        }


        phive('Tournament')->setPrizeAmount($entries, $t);

        if ($t['category'] == 'added' && !empty($t['guaranteed_prize_amount'])) {
            // Prize diff is the whole guaranteed as that is now regarded as the added amount.
            $prize_diff = $t['guaranteed_prize_amount'];
            // The prize amount is now the whole total of guaranteed and buy ins.
            $prize_amount = $t['guaranteed_prize_amount'] + $t['prize_amount'];
        } else if (!empty($t['guaranteed_prize_amount']) && $t['guaranteed_prize_amount'] > $t['prize_amount']) {
            $prize_diff = $t['guaranteed_prize_amount'] - $t['prize_amount'];
            $prize_amount = $t['guaranteed_prize_amount'];
        } else
            $prize_amount = $t['prize_amount'];

        foreach ($entries as $i => $e) {

            if (!phive('Tournament')->canGetPrize($e, $t))
                continue;

            $u = $users[$e['id']];
            $ud = $u->data;

            phive('Tournament')->wsCalcPrizeFinished($ud, $t, $e);


            $place = $i + 1;
            phive('Tournament')->winEvent($t, $place, $u->data);
            $percent = phive('Tournament')->getPrizePercent($t, $place);
            $prize = floor($percent * $prize_amount);
            $prize_changed = floor(phive('Tournament')->chgToUsr($u, $prize));
            $e['won_amount'] = $prize;

            $start_from = $last_result_place;
            if ($place >= $start_from) {
                phive('UserHandler')->logAction($u, "Won: $prize, Position: $place, Battle name: {$t['tournament_name']}, Battle id: {$t['id']}", 'battle');
                if ($t['prize_type'] == 'cash-fixed')
                    phive('Tournament')->returnCashBalance($u, $t, $e);
                $e['result_place'] = $place;
                if (!empty($prize))
                    phive('Tournament')->handleCashPrize($u, $prize_changed, $t, $prize_diff, $percent, $prize, $e);
                phive('Tournament')->saveEntry($e);
            }
        }

        phive('Tournament')->save($t);
        phive('Tournament')->handleBountyPrize($t);
        phive('Tournament')->initMem($t, false);
        echo "Leaderboard recalculated and prizes credited \n";
    }

}





