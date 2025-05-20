<?php
require_once "{$commons_folder}/common_functions.php";
require_once "{$commons_folder}/users/settings/settings.php";

$requester = "@";      // change requester
// $sc_id = 123;       // enable to override story Id if different from folder name
$post_shortcut = true; // enable posting of script output to shortcut, set false if script produces secret output

/**
 * Removes a cross_brand_check_block from a user which can stop the user to deposit.
 *
 * @param string $sc_id   Story Id used in logging and auditing
 * @param        $user_id The user id to remove block
 */

removeCrossBrandCheckBlock($sc_id, 11223344);
/**
 * Removes a cross_brand_check_block from multiple users which can stop the users to deposit.
 *
 * @param string $sc_id    Story Id used in logging and auditing
 * @param        $user_ids The array of user ids to remove block
 */
removeCrossBrandCheckBlockMulti($sc_id, [11223344, 55667788]);


/**
 * Removes a withdrawal_block from a user.
 *
 * @param string $sc_id   Story Id used in logging and auditing
 * @param        $user_id The user id to remove block
 */
removeWithdrawalBlock($sc_id, 11223344);

/**
 * Removes a withdrawal_block from multiple users.
 *
 * @param string $sc_id    Story Id used in logging and auditing
 * @param        $user_ids The array of user ids to remove block
 */
removeWithdrawalBlockMulti($sc_id, [11223344, 55667788]);


/**
 * Removes a play_block from a user.
 *
 * @param string $sc_id   Story Id used in logging and auditing
 * @param array  $user_id The user id to remove block
 */
removePlayBlock($sc_id, 11223344);

/**
 * Removes a play_block from multiple users.
 *
 * @param string $sc_id    Story Id used in logging and auditing
 * @param        $user_ids The array of user ids to remove block
 */
removePlayBlockMulti($sc_id, [11223344, 55667788]);
