<?php
require_once "{$root_folder_path}/phive/phive.php";
require_once "{$commons_folder}/common_functions.php";

$requester = "";           # change requester
# $sc_id = 123456;          # enable to override story ID if different from folder name
$post_shortcut = true;      # enable posting of script output - set false if script produces secret output
$close_story = true;        # enable closing story - set false if same story requires multiple scripts to be run
$push_script_output = true; # enable pushing story output to git - set false if not needed
$move_story_folder = true; # enable moving story folder to archived folder - set false of not needed

const PRODUCERS = [
    'LIVE' => 1,
    'PREMATCH' => 2,
];

$sql = Phive("SQL");
if (!isCli()) {
    die("The script must be run in a CLI environment.");
}

foreach (PRODUCERS as $producer_name => $producer_id) {
    echo "Checking $producer_name producer".PHP_EOL;
    //id|producer_id|event_ext_id|endpoint|request_id|is_issued|timestamp|snapshot_received|created_at|updated_at|
    $recovery_query = "SELECT r.* FROM sportsbook_uof.uof__recoveries r WHERE r.producer_id = $producer_id AND r.snapshot_received IS NULL AND r.is_issued = 0";
    $recoveries = $sql->doDb('sportsbook_uof')->loadArray($recovery_query);

    if (count($recoveries) === 0) {
        echo "Not found recoveries to fix for $producer_name producer".PHP_EOL;
        continue;
    }

    //id|ext_id|name|description|state|state_changed|api_url|active|scope|stateful_recovery_window_minutes|last_alive|subscribed|created_at|updated_at|last_alive_for_suspend|
    $producer_query = "SELECT p.* FROM sportsbook_uof.uof__producers p WHERE p.id = $producer_id";
    $producer = $sql->doDb('sportsbook_uof')->loadArray($producer_query);

    if ($producer['state'] != 'ALIVE') {
        $the_lowest_recovery_timestamp = min(array_filter(array_column($recoveries, 'timestamp')));
        /* im not sure if that should be overriden by $producer['last_alive_for_suspend'] if it's null */
        $the_lowest_recovery_timestamp = min($the_lowest_recovery_timestamp, $producer['last_alive_for_suspend']);
        /* todo timestamp shouldn't be longer than 10h for live and 72h for prematch, let's add logic for it, BUT PLEASE WE SHOULDN'T PICK IT THAT LATE */

        $update_producer_query = "UPDATE uof__producers SET last_alive_for_suspend = $the_lowest_recovery_timestamp WHERE id = $producer_id";
        echo "Updating producer $producer_name with last_alive_for_suspend with $the_lowest_recovery_timestamp".PHP_EOL;
        $sql->doDb('sportsbook_uof')->query($update_producer_query);
    }

    $recoveries_ids_to_update = array_column($recoveries, 'id');
    $recoveries_ids_in = $sql->makeIn($recoveries_ids_to_update);
    $update_recoveries_query = "UPDATE uof__recoveries SET is_issued = 1, snapshot_received = CURRENT_TIMESTAMP WHERE id IN($recoveries_ids_in)";
    echo "Forcing snapshot_received for recoveries $recoveries_ids_in".PHP_EOL;
    $sql->doDb('sportsbook_uof')->query($update_recoveries_query);

    echo "Updated".PHP_EOL;
}
