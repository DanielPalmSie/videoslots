<?php
require_once "{$commons_folder}/common_functions.php";
require_once "{$commons_folder}/users/documents/documents.php";

$requester = "@";      // change requester
// $sc_id = 123;       // enable to override story Id if different from folder name
$post_shortcut = true; // enable posting of script output to shortcut, set false if script produces secret output

/**
 * Add any missing idcard-pic/addresspic/bankpic documents for a particular user
 *
 * @param string $sc_id   Story Id used in logging and auditing
 * @param integer $user_id The user id to add missing documents
 * @return int The number of documents added
 */
addMissingDocuments($sc_id, 112233);


/**
 * Adds any missing idcard-pic/addresspic/bankpic documents to multiple users.
 *
 * @param string $sc_id    Story Id used in logging and auditing
 * @param array $user_ids The array of user ids to add missing documents
 */
addMissingDocumentsMulti($sc_id, [112233, 445566]);

/**
 * Add a document for a particular user
 *
 * @param string $sc_id   Story Id used in logging and auditing
 * @param integer $user_id The user id to add missing documents
 * @param string $tag     The tag (document) to add if it does not exist - See list of keys in library:
 */
addMissingDocument($sc_id, 112233, 'sourceoffunds');

/**
 * Add missing documents for a particular user
 *
 * @param string  $sc_id    Story Id used in logging and auditing
 * @param integer $user_id  The user id to add missing documents
 * @param string  $card_last_four Last 4 digits of card
 * @return int The number of documents added (0 or 1)
 */
addMissingDocumentCreditCards($sc_id, 112233, "1234");

/**
 * Add missing documents for a particular user
 *
 * @param string  $sc_id       Story Id used in logging and auditing
 * @param array   $user_cards  Array of user - cards of the format [ user_id => last_four ]
 */
addMissingDocumentCreditCardMulti($sc_id, [112233 => "1234", 445566 => "5678"]);
