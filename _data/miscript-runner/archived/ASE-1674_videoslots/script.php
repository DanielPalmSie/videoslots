<?php

require_once "{$root_folder_path}/phive/phive.php";
require_once "{$commons_folder}/common_functions.php";

$requester = "@petronelmorosanu";            # change requester
# $sc_id = 123456;           # enable to override story ID if different from folder name
$post_shortcut = true;       # enable posting of script output - set false if script produces secret output
$close_story = true;         # enable closing story - set false if same story requires multiple scripts to be run
$move_story_folder = true;   # enable moving story folder to archived folder - set false if not needed
$push_script_output = true;  # enable pushing story output to git - set false if not needed
$is_test = false;             # 'true' will override and disable the 4 variables above - set 'false' for production
$create_lockfile = true;     # handles creation and pushing of lockfile if set to true
# $extra_args can store additional parameter supplied from the pipeline

$sql = Phive("SQL");
if (!isCli()) {
    die("Error: the script must be run in a CLI environment" . PHP_EOL);
}

$csv_path = __DIR__ . "/micro_games_ELK_VS.csv";
$i = 0;

$games = readCsv($csv_path);

foreach ($games as $game) {
    $res = $sql->loadArray("SELECT * FROM micro_games WHERE id = {$game['id']} AND operator = 'ELK' AND blocked_countries  NOT LIKE '%IS%'");
    if (!empty($res)) {
        $sql->shs()->query("UPDATE micro_games
                    SET blocked_countries = CONCAT(blocked_countries, ' IS')
                    WHERE id = {$game['id']} AND operator = 'ELK'");

        echo " IS added as a blocked country to game: [{$game['game_name']}]   \n";
        $i++;
    } else {
        echo "IS is already blocked on game: [{$game['game_name']}] \n";
    }
}
echo "{$i} Games have been blocked \n";
