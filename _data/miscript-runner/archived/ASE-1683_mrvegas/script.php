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

//Updating User Occupation
$sql->sh(888006)->query("UPDATE users_settings SET value = 'Emergency Response Unit' WHERE user_id = 888006 AND id = 5960184587 AND setting = 'occupation' ");

$description = "Occupation updated to: Emergency Response Unit - ASE-1683";
phive('UserHandler')->logAction(888006, $description, 'comment', true, $system_user);

echo "Done!\n";
