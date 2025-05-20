<?php

require_once "{$root_folder_path}/phive/phive.php";
require_once "{$commons_folder}/common_functions.php";

$requester = "@petronelmorosanu";            # change requester
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

$csv_path = __DIR__ . "/Easter_wheels_MRV.csv";
$users = $to_save = readCsv($csv_path);

foreach ($users as $user) {

    for ($i = 1; $i <= $user['amount']; $i++) {
        phive("Trophy")->giveAward($user['award_id'], $user['user_id']);

        $descr = "manually added award id {$user['award_id']} - ASE-1665";
        phive('UserHandler')->logAction($user['user_id'], $descr, "comment", true, $system_user);
        echo " Award {$user['award_id']} has been granted to {$user['user_id']}\n";
    }

}
