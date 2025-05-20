<?php
require_once "{$commons_folder}/common_functions.php";
require_once "{$commons_folder}/partneroom/withdrawal_disapprovals/pr_withdrawal_disapprovals.php";

$requester = "@";      // change requester
// $sc_id = 123;       // enable to override story Id if different from folder name
$post_shortcut = true; // enable posting of script output to shortcut, set false if script produces secret output

/**
 * Disapprove a PR withdrawal and insert a Refund Transaction in PR cash_transaction table.
 *
 * @param string $sc_id            Story Id used in logging and auditing
 * @param        $disapproval_date The day to set disapproval and insert Normal Refund.
 *                                 This is usually the last day of last month.
 *                                 NOTE: Will cause issues if set before last month due to liabilities recorded in Phive.
 */ disapprovePendingWithdrawalsPr($sc_id, "2023-05-31", [111, 222, 333]);
