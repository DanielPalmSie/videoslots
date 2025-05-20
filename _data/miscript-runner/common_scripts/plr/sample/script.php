<?php
require_once "{$commons_folder}/common_functions.php";
require_once "{$commons_folder}/plr/plr.php";

$requester = "@";      // change requester
// $sc_id = 123;       // enable to override story Id if different from folder name
$post_shortcut = true; // enable posting of script output to shortcut, set false if script produces secret output


$year  = 2023;        // set year
$month = x;           // set month
$brand = 'VS/MRV';    // Choose brand to match filename

/**
 * Inserts liability adjustments from a csv file.
 *
 * @param string $sc_id         Story Id used in logging and auditing
 * @param string $full_csv_path The CSV file name (including full path) to be processed
 *                              CSV Columns:user_id, [currency],[username],[net_liab],[opening],[closing],[diff],[abs_diff],[abs_diff_in_eur],[mod], country,province,[adjustment_amount],[type],description,[total_liability_amount],specific_liability_amount,liability_type,[details]
 * @param  int $year            Year
 * @param  int $month           & month to process
 */
insertLiabilityAdjustments($sc_id, __DIR__ . "/liability_adjustments_{$brand}.csv", $year, $month);

/**
 * Moves Jackpot Wins to Win category in the PLR for a given year and month and marks them with the network.
 * This is needed because most jackpot wins are registered under wins and therefore needs to be unified.
 *
 * @param  string $sc_id  Story Id used in logging and auditing
 * @param  int $year      Year
 * @param  int $month     & month to process
 */
recategorizeJackpotWinsAsNormalWin($sc_id, $year, $month);
