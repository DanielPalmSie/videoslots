<?php
require_once "{$root_folder_path}/phive/phive.php";
require_once "{$commons_folder}/common_functions.php";
require_once "{$commons_folder}/crm/emails/emails.php";

$requester = "@andresmunoz";      // change requester
// $sc_id = 123;       // enable to override story Id if different from folder name
$post_shortcut = true;       # enable posting of script output - set false if script produces secret output
$close_story = true;         # enable closing story - set false if same story requires multiple scripts to be run
$move_story_folder = true;  # enable moving story folder to archived folder - set false if not needed
$push_script_output = true;  # enable pushing story output to git - set false if not needed
$create_lockfile = true;
$is_test = false;

$sql = Phive("SQL");
if (!isCli()) {
    die("Error: the script must be run in a CLI environment" . PHP_EOL);
}

/**
 * Update Email Config from csv, typically runs at last day of the month.
 *
 * @param string $sc_id         Story Id used in logging and auditing
 * @param string $full_csv_path The CSV file name (including full path) to be processed
 *                              CSV Columns: id,[config_name],[config_tag],config_value,[OLD config_value]
 */
updateEmailConfigFromCsv($sc_id, __DIR__ . "/{$sc_id}_KS.csv");
