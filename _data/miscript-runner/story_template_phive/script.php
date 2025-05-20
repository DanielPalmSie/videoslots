<?php

require_once "{$root_folder_path}/phive/phive.php";
require_once "{$commons_folder}/common_functions.php";

$requester = "@";            # change requester
# $sc_id = 123456;           # enable to override story ID if different from folder name
$post_shortcut = true;       # enable posting of script output - set false if script produces secret output
$close_story = true;         # enable closing story - set false if same story requires multiple scripts to be run
$move_story_folder = true;   # enable moving story folder to archived folder - set false if not needed
$push_script_output = true;  # enable pushing story output to git - set false if not needed
$is_test = true;             # 'true' will override and disable the 4 variables above - set 'false' for production
$create_lockfile = true;     # handles creation and pushing of lockfile if set to true
# $extra_args can store additional parameter supplied from the pipeline

$sql = Phive("SQL");
if (!isCli()) {
    die("Error: the script must be run in a CLI environment" . PHP_EOL);
}

# change script below, as required:
$result = $sql->loadArray("SELECT * FROM users ORDER BY ID DESC LIMIT 1;")[0];
echo "Master DB | Last user_id: {$result['id']}\n";

for ($i = 0; $i <= 9; $i++) {
    $result = $sql->sh($i)->loadArray("SELECT * FROM bets ORDER BY ID DESC LIMIT 1;")[0];
    echo "Node {$i} | Last bet created_at: {$result['created_at']}\n";
}
