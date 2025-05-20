<?php

require_once "${root_folder_path}/phive/phive.php";
require_once "${commons_folder}/common_functions.php";

$requester           = "daniel.palm";
$post_shortcut       = true;
$close_story         = true;
$move_story_folder   = true;
$push_script_output  = true;
$is_test             = false;
$create_lockfile     = true;

$sql = Phive("SQL");
if (!isCli()) {
    die("Error: the script must be run in a CLI environment" . PHP_EOL);
}

echo "=== Updating menus: adding 'SE' to excluded_countries where name LIKE '%cashback%' ===\n";

$affected = $sql->query("
    UPDATE menus
    SET excluded_countries = CASE
        WHEN excluded_countries IS NULL OR excluded_countries = ''
            THEN 'SE'
        WHEN FIND_IN_SET('SE', excluded_countries) = 0
            THEN CONCAT(excluded_countries, ',SE')
        ELSE excluded_countries
    END
    WHERE name LIKE '%cashback%'
");

$affectedWinbooster = $sql->query("
    UPDATE menus
    SET excluded_countries = CASE
        WHEN excluded_countries IS NULL OR excluded_countries = '' THEN 'SE'
        WHEN FIND_IN_SET('SE', excluded_countries) = 0            THEN CONCAT(excluded_countries, ',SE')
        ELSE excluded_countries
    END
    WHERE link_page_id IN (289, 295)
");

echo "Winbooster mobile menus updated: {$affectedWinbooster}\n";

echo "Rows updated: {$affected}\n";
