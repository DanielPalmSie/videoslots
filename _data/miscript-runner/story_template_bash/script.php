<?php

$bash_script = "bash_script.sh";

$requester = "@";            # change requester
# $sc_id = 123456;           # enable to override story ID if different from folder name
$post_shortcut = true;       # enable posting of script output - set false if script produces secret output
$close_story = true;         # enable closing story - set false if same story requires multiple scripts to be run
$move_story_folder = false;  # enable moving story folder to archived folder - set false if not needed
$push_script_output = true;  # enable pushing story output to git - set false if not needed
$is_test = true;             # 'true' will override and disable the 4 variables above - set 'false' for production
$create_lockfile = true;     # handles creation and pushing of lockfile if set to true
# $extra_args can store additional parameter supplied from the pipeline - add after story_folder

# $root_folder_path = "{$root_folder_path}/diamondbet/soap/IT";  # Uncomment for running IT scripts in DiamondBet IT folder

passthru("bash " . __DIR__ . "/{$bash_script} {$root_folder_path} {$story_folder}", $return_code);

if ($return_code) {
    echo "Error: bash script failed, status: '$return_code'" . PHP_EOL;
    exit($return_code);
}
