<?php
require_once "{$commons_folder}/common_functions.php";
require_once "{$commons_folder}/payments/deposits/deposits.php";

$requester = "@";
$brand = 'vs';
//$brand = 'mrv';

$csv_path = __DIR__ . "/deposits_to_credit_{$brand}.csv";
$deposits = readCsv($csv_path);
creditDeposits($sc_id, $deposits);
