<?php
require_once "{$commons_folder}/common_functions.php";
require_once "{$commons_folder}/payments/donations/donations.php";

$requester = "@";      // change requester
// $sc_id = 123;       // enable to override story Id if different from folder name
$post_shortcut = true; // enable posting of script output to shortcut, set false if script produces secret output

/**
 * Loops through a list of self-excluded GB users, and if inactive and balance unchanged, zero out balance.
 * Generates a report (csv) of actions, for donations to YGAM.
 *
 * @param string $sc_id         Story Id used in logging and auditing
 * @param string $full_csv_path The CSV file name (including full path) to be processed
 *                              CSV Columns: user_id,cash_balance,currency,country,Internal exclusion requested,Internal exclusion will end,External exclusion received
 * @param boolean $test         Will not change balance if test = true
 */

$brand = 'VS/MRV'; // Choose brand to match filename

donateSelfExcludedBalances($sc_id, __DIR__ . "/SC{$sc_id}_{$brand}.csv", false);
