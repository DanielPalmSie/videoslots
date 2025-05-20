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


$csv_path = __DIR__ . "/bonuses_mgr.csv";

$bonuses = readCsv($csv_path);

foreach ($bonuses as $bonus) {

    $sql->query("UPDATE bonus_types SET frb_denomination = 20 WHERE id = {$bonus['id']}");
    $sql->shs()->query("UPDATE bonus_types SET frb_denomination = 20 WHERE id = {$bonus['id']}");

    $res = $sql->shs()->LoadArray("SELECT id, bonus_name, frb_denomination FROM bonus_types WHERE id = {$bonus['id']}");

    echo "Bonus ID {$res[0]['id']} - New frb_denomination value : {$res[0]['frb_denomination']}\n";
}
