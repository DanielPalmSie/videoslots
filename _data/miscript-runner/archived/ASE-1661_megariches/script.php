<?php
require_once "{$root_folder_path}/phive/phive.php";
require_once "{$commons_folder}/common_functions.php";

$requester = "@gabrielwerlich";            # change requester
// $sc_id = 123;       // enable to override story Id if different from folder name
$post_shortcut = true;       # enable posting of script output - set false if script produces secret output
$close_story = true;         # enable closing story - set false if same story requires multiple scripts to be run
$move_story_folder = true;  # enable moving story folder to archived folder - set false if not needed
$push_script_output = true;  # enable pushing story output to git - set false if not needed
$create_lockfile = true;     # handles creation and pushing of lockfile if set to true
$is_test = false;             # 'true' will override and disable the 4 variables above - set 'false' for production

$sql = Phive("SQL");
if (!isCli()) {
    die("Error: the script must be run in a CLI environment" . PHP_EOL);
}

$sql->query("UPDATE micro_games SET active = 0 WHERE operator = 'Cayetano Games' ");
$sql->shs()->query("UPDATE micro_games SET active = 0 WHERE operator = 'Cayetano Games' ");
