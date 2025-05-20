<?php
require_once "{$commons_folder}/common_functions.php";
require_once "{$commons_folder}/payments/deposits/deposits.php";

$csv_path_for_transactions_to_check = __DIR__ . "/initial_data.csv";
initialChecks($csv_path_for_transactions_to_check);
