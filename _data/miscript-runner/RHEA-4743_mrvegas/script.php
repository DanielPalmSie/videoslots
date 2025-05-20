<?php

require_once "{$root_folder_path}/phive/phive.php";
require_once "{$commons_folder}/common_functions.php";

$requester = "@dylan.grech";            # change requester
# $sc_id = 123456;           # enable to override story ID if different from folder name
$post_shortcut = true;       # enable posting of script output - set false if script produces secret output
$close_story = false;         # enable closing story - set false if same story requires multiple scripts to be run
$move_story_folder = false;   # enable moving story folder to archived folder - set false if not needed
$push_script_output = true;  # enable pushing story output to git - set false if not needed
$is_test = false;            # 'true' will override and disable the 4 variables above - set 'false' for production
$create_lockfile = true;     # handles creation and pushing of lockfile if set to true
# $extra_args can store additional parameter supplied from the pipeline

if (!isCli()) {
    die("Error: the script must be run in a CLI environment" . PHP_EOL);
}

$_triggers = ['welcome.mail', 'nodeposit-newbonusoffers-mail-1', 'no-deposit-freeroll'];
$_users = [889224];

/** @var MailHandler2 $mh */
$mh = phive('MailHandler2');

/** @var PrivacyHandler $ph */
$ph = phive('DBUserHandler/PrivacyHandler');

foreach ($_triggers as $t) {
    foreach ($_users as $u) {
        $user = cu($u);
        if (empty($user)) {
            echo("user $u not found." . PHP_EOL);
            continue;
        }

        $trigger = $ph->getTriggerSettings($ph::CHANNEL_EMAIL, $t);
        if (!$trigger) {
            echo("trigger $t not found in PrivacyHandler email_triggers configs");
            continue;
        }

        $form = $mh->getSetting("DEFAULT_FROM_EMAIL");
        $replacers = [
            '__USERNAME__'  => $user->getUsername(),
            '__FIRSTNAME__' => 'test name',
            '__DAYS__'      => 2,
            '__VOUCHERCODE__'   => 'ABC',
            '__COUNT__'   => 345,
            '__CURRENCY__'  => 'EUR',
            '__RELOADCODE__'  => 'ABC',
            '__EXTRAAMOUNT__'   => 123,
            '__EXTRA__' => 0,
            '__GAME__' => 'ABC',
            '__AMOUNT__' => 123,
            '__WAGERREQ__' => 345
        ];

        $result = $mh->sendMail($t, $user, $replacers, null, $form, $form, null, null, $mh::PRIORITY_NEWSLETTER);
        $addr = $user->getAttr('email');

        if ($result) {
            echo "email {$t} sent to {$addr}" . PHP_EOL;
        } else {
            echo "email {$t} not sent to {$addr}" . PHP_EOL;
        }
    }
}

