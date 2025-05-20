<?php

/**
 * Add a country to the list of blocked countries for a games network
 *
 * @param string $sc_id Story id used in logging and auditing.
 * @param string $network The game network that we will be targeting for the blocks
 * @param string $country The country that needs blocking
 */
function blockNetworkCountry(string $sc_id, string $network, string $country)
{
    echo "Adding country {$country} to blocked_countries for games in {$network} network\n";
    $games_already_country_blocked = Phive('SQL')->loadAssoc("SELECT count(*) as cnt FROM micro_games WHERE network = '{$network}' AND blocked_countries LIKE '%{$country}%'")['cnt'];

    Phive('SQL')->shs()->query("UPDATE micro_games SET blocked_countries = CONCAT(blocked_countries, ' {$country}') WHERE network = '{$network}' AND blocked_countries NOT LIKE '%{$country}%'");

    $games_in_network      = Phive('SQL')->loadAssoc("SELECT count(*) as cnt FROM micro_games WHERE network = '{$network}'")['cnt'];
    $games_country_blocked = Phive('SQL')->loadAssoc("SELECT count(*) as cnt FROM micro_games WHERE network = '{$network}' AND blocked_countries LIKE '%{$country}%'")['cnt'];

    echo "Number of games in {$network} which already had {$country} country_block: {$games_already_country_blocked} \n";
    echo "Number of games in {$network} which are now {$country} country_blocked: {$games_country_blocked}  (out of {$games_in_network} in network)\n";
    $records_updated = $games_country_blocked - $games_already_country_blocked;
    echo "DONE country {$country} - {$records_updated} records updated\n\n";
}

/**
 * Add multiple countries to the list of blocked countries for a games network
 *
 * @param string $sc_id Story id used in logging and auditing.
 * @param string $network The game network that we will be targeting for the blocks
 * @param array $countries The countries that needs blocking
 */
function blockNetworkCountries(string $sc_id, string $network, array $countries)
{
    echo " Add multiple countries to the list of blocked countries for a {$network} network  -----\n\n";
    $total_countries = 0;
    foreach ($countries as $country) {
        blockNetworkCountry($sc_id, $network, $country);
        $total_countries++;
    }
    echo "Processed all games - added {$total_countries} countries to blocked for {$network} -----\n\n";
    echo "DONE ----- \n";
}

/**
 * Remove a country to the list of blocked countries for a games network
 *
 * @param string $sc_id Story id used in logging and auditing.
 * @param string $network The game network that we will be targeting for the unblocks
 * @param string $country The country that needs unblocking
 */
function unblockNetworkCountry(string $sc_id, string $network, string $country)
{
    echo "Removing country {$country} from blocked_countries for games in {$network} network\n";
    $games_already_country_unblocked = Phive('SQL')->loadAssoc("SELECT count(*) as cnt FROM micro_games WHERE network = '{$network}' AND blocked_countries NOT LIKE '%{$country}%'")['cnt'];

    Phive('SQL')->shs()->query(" UPDATE micro_games
                                 SET blocked_countries = IF (LEFT(blocked_countries,2) = '{$country}', REPLACE(blocked_countries,'{$country} ', ''), REPLACE(blocked_countries,' $country', ''))
                                 WHERE network = '{$network}' AND blocked_countries LIKE '%{$country}%';");

    $games_in_network        = Phive('SQL')->loadAssoc("SELECT count(*) as cnt FROM micro_games WHERE network = '{$network}'")['cnt'];
    $games_country_unblocked = Phive('SQL')->loadAssoc("SELECT count(*) as cnt FROM micro_games WHERE network = '{$network}' AND blocked_countries NOT LIKE '%{$country}%'")['cnt'];

    echo "Number of games in {$network} which already did not have {$country} country_block: {$games_already_country_unblocked} \n";
    echo "Number of games in {$network} which are now {$country} country_unblocked: {$games_country_unblocked}  (out of {$games_in_network} in network)\n";
    $records_updated = $games_country_unblocked - $games_already_country_unblocked;
    echo "DONE country {$country} - {$records_updated} records updated\n\n";
}

/**
 * Remove multiple countries from the list of blocked countries for a games network
 *
 * @param string $sc_id Story id used in logging and auditing.
 * @param string $network The game network that we will be targeting for the unblocks
 * @param array $countries The countries that needs unblocking
 */
function unblockNetworkCountries(string $sc_id, string $network, array $countries)
{
    echo " Remove multiple countries from the list of blocked countries for a {$network} network  -----\n\n";
    $total_countries = 0;
    foreach ($countries as $country) {
        unblockNetworkCountry($sc_id, $network, $country);
        $total_countries++;
    }
    echo "Processed all countries - removed {$total_countries} countries from blocked list for {$network} network -----\n\n";
    echo "DONE ----- \n";
}
