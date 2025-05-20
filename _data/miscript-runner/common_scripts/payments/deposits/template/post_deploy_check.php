<?php
require_once "{$commons_folder}/common_functions.php";
require_once "{$commons_folder}/payments/deposits/deposits.php";

$csv_path_to_transaction_results = __DIR__ . "/script_results.csv";
postDeployChecks($csv_path_to_transaction_results);
