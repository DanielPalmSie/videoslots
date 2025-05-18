<?php
ini_set('max_execution_time', '3000');
require_once __DIR__ . '/../../phive/phive.php';

if(isCli()){
    $GLOBALS['is_cron'] = true;

    $logger = phive('Logger')->getLogger('cron');
    $logger->info('every_hour: diamondbet/soap/every_hour.php started');

    $start_date = date('Y-m-d', strtotime('yesterday'));
    $end_date = date('Y-m-d', strtotime('today'));

    $logger->info('every_hour: onEveryHour started');
    lics('onEveryHour');
    $logger->info('every_hour: onEveryHour finished');


    $logger->info('every_hour: incompleteSourceOfWealthForXDays started');
    phive('Cashier/Aml')->incompleteSourceOfWealthForXDays();
    $logger->info('every_hour: incompleteSourceOfWealthForXDays finished');

    $logger->info('every_hour: checkPendingWithdrawals started');
    phive('CasinoCashier')->checkPendingWithdrawals();
    $logger->info('every_hour: checkPendingWithdrawals finished');

    try {
        $logger->info('every_hour: triggerUsersWageringInLastYHours started');
        phive('Cashier/Rg')->triggerUsersWageringInLastYHours();
        $logger->info('every_hour: triggerUsersWageringInLastYHours finished');
    } catch (Exception $e) {
        error_log("Error executing triggerUsersWageringInLastYHours: " . $e->getMessage());
    }

    $logger->info('every_hour: Cashier/Arf everyHourCron started');
    phive()->pexec('Cashier/Arf', 'invoke', ['everyHourCron']);
    $logger->info('every_hour: Cashier/Arf everyHourCron finished');

    try {
        phive('Licensed')->notifyCustomersOnIgnoredRgPopup();
    } catch (Exception $e) {
        error_log("notifyCustomersOnIgnoredRgPopup Failed: {$e->getMessage()}");
    }

    // We loop instead to avoid the host from taking a massive CPU hit
    //phive('SQL')->loopShardsSynced(function($db, $sh_conf, $sh_num){
    //    phive('Trophy')->completeCron($sh_num);
    //});
    //pExecShards('Trophy', 'completeCron');
}
