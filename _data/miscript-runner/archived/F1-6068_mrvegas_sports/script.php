<?php

$bash_script = "bash_script.sh";

$requester = "@victoria.essien @oliver.grech";      // change requester
// $sc_id = 123;       // enable to override story Id if different from folder name
$post_shortcut = true; // enable posting of script output to shortcut, set false if script produces secret output
$close_story = true;        # enable closing story - set false if same story requires multiple scripts to be run
$push_script_output = true; # enable pushing story output to git - set false if not needed
$move_story_folder = true; # enable moving story folder to archived folder - set false of not needed

// $root_folder_path = "{$root_folder_path}/diamondbet/soap/IT"; // Uncomment for running IT scripts in DiamondBet IT folder

passthru("bash " . __DIR__ . "/{$bash_script} {$root_folder_path} {$story_folder}", $return_code);

if ($return_code) {
    echo "Error: bash script failed, status {$return_code}";
    exit($return_code);
}
