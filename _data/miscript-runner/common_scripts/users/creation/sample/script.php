<?php
require_once "{$commons_folder}/common_functions.php";
require_once "{$commons_folder}/users/creation/user_creation.php";

$requester = "@";      // change requester
// $sc_id = 123;       // enable to override story Id if different from folder name
$post_shortcut = true; // enable posting of script output to shortcut, set false if script produces secret output

$brand = 'MRV';
createTestAccountsCsv($sc_id, __DIR__ . "/Test_accounts_to_create_{$brand}.csv");
