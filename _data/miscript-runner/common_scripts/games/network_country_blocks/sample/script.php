<?php
require_once "{$commons_folder}/common_functions.php";
require_once "{$commons_folder}/games/network_country_blocks/network_country_blocks.php";

$requester = "@";      // change requester
// $sc_id = 123;       // enable to override story Id if different from folder name
$post_shortcut = true; // enable posting of script output to shortcut, set false if script produces secret output

blockNetworkCountry($sc_id, 'pushgaming', 'RU');
unblockNetworkCountry($sc_id, 'pushgaming', 'RU');
blockNetworkCountries($sc_id, 'pushgaming', ['RU', 'JP']);
unblockNetworkCountries($sc_id, 'pushgaming', ['RU', 'JP']);
