<?php

require_once "{$root_folder_path}/phive/phive.php";
require_once "{$commons_folder}/common_functions.php";

$requester = "@maria.liplianina";            # change requester
# $sc_id = 123456;           # enable to override story ID if different from folder name
$post_shortcut = true;       # enable posting of script output - set false if script produces secret output
$close_story = true;         # enable closing story - set false if same story requires multiple scripts to be run
$move_story_folder = false;   # enable moving story folder to archived folder - set false if not needed
$push_script_output = true;  # enable pushing story output to git - set false if not needed
$is_test = false;            # 'true' will override and disable the 4 variables above - set 'false' for production
$create_lockfile = true;     # handles creation and pushing of lockfile if set to true
# $extra_args can store additional parameter supplied from the pipeline

if (!isCli()) {
    die("Error: the script must be run in a CLI environment" . PHP_EOL);
}

const CUSTOMER_IDS = [100, 102, 103, 104];

echo "Updating MTS accounts and DMAPI documents for Videoslots/Kungaslottet/MegaRiches/Dbet has been started!\n";

require_once __DIR__ . "/migrate.php";
