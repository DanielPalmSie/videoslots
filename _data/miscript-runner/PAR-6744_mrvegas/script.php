<?php

$bash_script = "bash_script.sh";

$requester = "@darko.miloradovic";  # change requester
$post_shortcut = true;              # enable posting of script output - set false if script produces secret output
$close_story = false;               # enable closing story - set false if same story requires multiple scripts to be run
$move_story_folder = true;          # enable moving story folder to archived folder - set false of not needed
$push_script_output = true;         # enable pushing story output to git - set false if not needed


passthru("bash " . __DIR__ . "/{$bash_script} {$root_folder_path} {$story_folder}", $return_code);

if ($return_code) {
    echo "Error: bash script failed, status: {$return_code}\n";
    exit($return_code);
}
