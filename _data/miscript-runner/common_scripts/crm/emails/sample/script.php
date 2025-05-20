<?php
require_once "{$commons_folder}/common_functions.php";
require_once "{$commons_folder}/crm/emails/emails.php";
require_once "{$root_folder_path}/phive/phive.php";
$requester = '@';
$post_shortcut = false;      # enable posting of script output - set false if script produces secret output
$close_story = false;        # enable closing story - set false if same story requires multiple scripts to be run
$push_script_output = false; # enable pushing story output to git - set false if not needed
$move_story_folder = false;  # enable moving the file to archive once story is run

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
 * @var  string  $brand         Taken from the pipeline configuration, the brand in the csv needs to be called with the full name.
 *                              CSV Name example: [ASE-1_videoslots.csv / ASE-1_mrvegas.csv]
 */

updateEmailConfigFromCsv($sc_id, __DIR__ . "/{$sc_id}_{$brand}.csv");

/**
 * Manual Email Send Out from csv, used for CRM promotions that are not included in the monthly Schedule,
 *
 * @param string $sc_id         Story Id used in logging and auditing
 * @param string $full_csv_path The CSV file name (including full path) to be processed
 *                              CSV Columns: user_id
 * @param string $lang          Language of the email - [PROVIDED IN THE TICKET]
 * @param string $mail_trigger  Trigger of email template - [PROVIDED IN THE TICKET]
 * @var  string  $brand         Taken from the pipeline configuration, the brand in the csv needs to be called with the full name.
 *                              CSV Name example: [ASE-1_videoslots.csv / ASE-1_mrvegas.csv]
 */

manualEmailSendOutFromCsv($sc_id, __DIR__ . "/{$sc_id}_{$brand}.csv","en", "voucher-fail-change-of-game");
