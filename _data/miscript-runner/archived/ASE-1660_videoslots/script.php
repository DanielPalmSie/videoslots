<?php

require_once "{$root_folder_path}/phive/phive.php";
require_once "{$commons_folder}/common_functions.php";

$requester = "@YuriVelkis";            # change requester
# $sc_id = 123456;           # enable to override story ID if different from folder name
$post_shortcut = true;       # enable posting of script output - set false if script produces secret output
$close_story = true;         # enable closing story - set false if same story requires multiple scripts to be run
$move_story_folder = true;   # enable moving story folder to archived folder - set false if not needed
$push_script_output = true;  # enable pushing story output to git - set false if not needed
$is_test = false;             # 'true' will override and disable the 4 variables above - set 'false' for production
$create_lockfile = true;     # handles creation and pushing of lockfile if set to true
# $extra_args can store additional parameter supplied from the pipeline

$sql = Phive("SQL");
if (!isCli()) {
    die("Error: the script must be run in a CLI environment" . PHP_EOL);
}

$csv_path = __DIR__ . "/users_to_unblock_vs.csv";
$users = readCsv($csv_path);

foreach($users as $user) {
    $user_id = $user['user_id'];
    $u = cu($user_id);

    $reason = '';

    if ($u->isSuperBlocked()) {
        $reason = 'SuperBlocked';
    } elseif ($u->isSelfExcluded()) {
        $reason = 'SelfExcluded';
    } elseif (Phive("DBUserHandler")->isExternalSelfExcluded($u)) {
        $reason = 'ExternalSelfExcluded';
    }

    if ($reason) {
        echo "{$user_id} - Could not be unblocked, the user is: {$reason}.\n";
    } else {
        $res = $sql->sh($user_id)->LoadArray("SELECT active FROM users WHERE id = {$user_id}");

        if ($res[0]['active'] == 1) {
            echo "{$user_id} - Is already active.\n";
        } else {
            $u->setAttribute('active', '1');
            phive('UserHandler')->logAction($user_id, 'The user has been activated - ASE-1660.', 'comment');
            echo "{$user_id} - Has been activated.\n";
        }
    }
}
