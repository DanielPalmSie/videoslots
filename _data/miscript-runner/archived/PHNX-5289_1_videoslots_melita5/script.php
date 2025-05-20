<?php

require_once "{$root_folder_path}/phive/phive.php";
require_once "{$commons_folder}/common_functions.php";

$requester = "@aleksandar.bekjarovski";            # change requester
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

# change script below, as required:
phive('Licensed/DK/DK');

$dk = new SAFE('DK', phive('Licensed')->getSetting('DK'));

$cancel_records = [
    '2025-03-06' => [2034682, 2034932],
    '2025-03-12' => [2036267, 2036517],
    '2025-03-15' => [2037024, 2037275],
];

$dk->removeReportRunning();

foreach ($cancel_records as $date => $tokens) {
    $retries = 1;
    $is_another_report_running = $dk->checkRunningReport($dk::TAMPER_TOKEN_RUNNING_REPORT, false);
    while ($is_another_report_running === true && $retries <= 5) {
        echo "Attempt #$retries. There is another report running. Retrying in 15 seconds... \n";
        sleep(15);
        $retries++;
        $is_another_report_running = $dk->checkRunningReport($dk::TAMPER_TOKEN_RUNNING_REPORT, false);
    }

    if ($is_another_report_running === true) {
        echo "Could not cancel the reports for $date.\n";
        echo "Exiting cancellation.\n";
        die(1);
    }

    $dk->extractParams();
    $current_cursor_to_restore = $dk->getCursor();

    sleep(1);
    $dk->cancelXmlByDate($date, $date, '/tmp', 'all', $tokens);
    $dk->setCursor($current_cursor_to_restore);

    sleep(5);
}

$dk->removeReportRunning();
