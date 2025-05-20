<?php

require_once "{$root_folder_path}/phive/phive.php";
require_once "{$commons_folder}/common_functions.php";
require_once "{$commons_folder}/plr/plr.php";

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


$sql->sh(704096)->query("UPDATE users_monthly_liability SET country = 'CA', province = 'ON' WHERE id =19873517");

$to_update = readCsv(__DIR__ . "/mrv_report2.csv");
if (!empty($to_update)) {
    foreach ($to_update as $r) {
        echo "{$r['user_id']} {$r['new_country']} {$r['new_province']} {$r['id']} {$r['shard_id']}\n";
        $sql->query("
            UPDATE users_monthly_liability
            SET country = '{$r['new_country']}', province = '{$r['new_province']}'
            WHERE id = {$r['id']};
        ");
        $sql->sh($r['user_id'])->query("
            UPDATE users_monthly_liability
            SET country = '{$r['new_country']}', province = '{$r['new_province']}'
            WHERE id = {$r['shard_id']};
        ");
    }
}
