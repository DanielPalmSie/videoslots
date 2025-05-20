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

$year = 2025;        // set year
$month = 4;          // set month

//Uncategorized
#Master update
echo "\n1. Non-categorised on master\n";
$csv_path = __DIR__ . "/uncate_master.csv";
$csv = readCsv($csv_path);
foreach ($csv as $r) {
    echo "+";
    $sql->query("UPDATE users_monthly_liability SET sub_cat = '{$r['new_sub_cat']}' WHERE user_id = {$r['user_id']} AND id = {$r['id']} AND year = $year AND month = $month");
}

#Shards update
echo "\n2. Non-categorised on shards\n";
$csv_path = __DIR__ . "/uncate_shards.csv";
$csv = readCsv($csv_path);
foreach ($csv as $r) {
    echo "+";
    $sql->sh($r['user_id'])->query("UPDATE users_monthly_liability SET sub_cat = '{$r['new_sub_cat']}' WHERE user_id = {$r['user_id']} AND id = {$r['id']} AND year = $year AND month = $month");
}


$sql->query("UPDATE users_monthly_liability SET country = 'CA' WHERE id IN(54947590,
54960721,
54965113,
54969372,
54969373,
54971414,
54976465,
55055849)");

$sql->sh(886414)->query("UPDATE users_monthly_liability SET country = 'CA' WHERE id IN (19333185,
19464495,
19508415,
19551005,
19551015,
19571425,
19621935)");

$sql->sh(704096)->query("UPDATE users_monthly_liability SET country = 'NZ' WHERE id =19873517");

insertLiabilityAdjustments($sc_id, __DIR__ ."/liability_adjustments_mrv.csv", $year, $month);
