<?php
require_once "{$commons_folder}/common_functions.php";
require_once "{$commons_folder}/payments/withdrawals/withdrawals.php";

$requester = "@";      // change requester
// $sc_id = 123;       // enable to override story Id if different from folder name
$post_shortcut = true; // enable posting of script output to shortcut, set false if script produces secret output

/**
 * Loops through a list of withdrawals to update them and update the status and reference_id in mts.
 *
 * @param string $sc_id         Story Id used in logging and auditing
 * @param string $full_csv_path The CSV file name (including full path) to be processed
 *                              CSV Columns: user_id,withdrawal_id,ext_id,new_status,refund
 */
updateWithdrawals($sc_id, __DIR__ . '/withdrawals_to_update.csv');
