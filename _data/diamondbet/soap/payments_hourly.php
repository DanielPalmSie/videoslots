<?php
ini_set('max_execution_time', '30000');
require_once __DIR__ . '/../../phive/phive.php';

if(isCli()){
    $GLOBALS['is_cron'] = true;
    $c = phive('Cashier');
    $b = phive('DBUserHandler/Booster');
    $conf = phive('Config');
    if((int)date('N') === 1 && $conf->getValue('auto', 'clash') == 'yes') {
        if (phive('Race')->getSetting('clash_of_spins'))
            phive('Race')->payAwards();
        else
            $c->payQdTransactions(32, '', true);
    }

    phive('MailHandler2')->notifyException(static function() use ($b, $c, $conf) {
        if((int)date('N') === 5 && $conf->getValue('auto', 'booster') === 'yes') {
            $c->payQdTransactions(31, '', true);
            // new weekend booster (Vault) automatic payout for last week.
            $b->scheduledAutoRelease();
        }
    }, "Booster Vault Release");
}
