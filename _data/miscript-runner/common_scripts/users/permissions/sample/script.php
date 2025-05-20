<?php
require_once "{$commons_folder}/common_functions.php";
require_once "{$commons_folder}/users/permissions/permissions.php";

$requester = "@";      // change requester
// $sc_id = 123;       // enable to override story Id if different from folder name
$post_shortcut = true; // enable posting of script output to shortcut, set false if script produces secret output

/**
 * Add a list of permission tags to a group.
 *
 * @param string $sc_id     Story Id used in logging and auditing
 * @param int    $group_id  The group ID to add tags to
 * @param string $tag       A tag to add to group
 */
insertPermissionTag($sc_id, 123,  'tag1');

/**
 * Add permission tags to specific groups from a csv file.
 *
 * @param string $sc_id         Story Id used in logging and auditing
 * @param string $full_csv_path The CSV file name (including full path) to be processed
 *                              CSV Columns: [group_id],[tag]
 */
$brand = 'MRV';
insertPermissionTagsCsv($sc_id, __DIR__ . "/permission_tags_to_be_added_{$brand}.csv");

/**
 * Add a list of permission tags to a group.
 *
 * @param string $sc_id     Story Id used in logging and auditing
 * @param int    $group_id  The group ID to add tags to
 * @param array  $tags      An array of tags to add to the group
 */
insertPermissionTagsMulti($sc_id, 123, [
    'tag1',
    'tag2',
    'tag3'
]);
