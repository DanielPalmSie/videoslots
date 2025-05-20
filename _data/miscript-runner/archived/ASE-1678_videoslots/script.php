<?php
require_once "{$root_folder_path}/phive/phive.php";
require_once "{$commons_folder}/common_functions.php";
require_once "{$commons_folder}/bos/recalc_tourments.php";


$requester = "@YuriVelkis";            # change requester
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

Recalc_Tournament_All_Shards(5621771);
Recalc_Tournament_All_Shards(5624578);
Recalc_Tournament_All_Shards(5631003);
Recalc_Tournament_All_Shards(5637727);
Recalc_Tournament_All_Shards(5639285);
Recalc_Tournament_All_Shards(5641041);
Recalc_Tournament_From_Last_Place(5627328, 3);
Recalc_Tournament_From_Last_Place(5629690, 3);
Recalc_Tournament_From_Last_Place(5634180, 4);
Recalc_Tournament_From_Last_Place(5638519, 5);
