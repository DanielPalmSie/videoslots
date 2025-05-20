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

$dk = phive('Licensed/DK/DK');

$safe = new SAFE('DK', phive('Licensed')->getSetting('DK'));

$tokens = [
    [
        'token' => '2034682',
        'sequence_from' => 116,
        'sequence_to' => 287,
        'date' => '2025-03-06',
    ],
    [
        'token' => '2034932',
        'sequence_from' => 1,
        'sequence_to' => 116,
        'date' => '2025-03-06',
    ],
    [
        'token' => '2036267',
        'sequence_from' => 96,
        'sequence_to' => 283,
        'date' => '2025-03-12',
    ],
    [
        'token' => '2036517',
        'sequence_from' => 1,
        'sequence_to' => 96,
        'date' => '2025-03-12',
    ],
    [
        'token' => '2037024',
        'sequence_from' => 99,
        'sequence_to' => 288,
        'date' => '2025-03-15',
    ],
    [
        'token' => '2037275',
        'sequence_from' => 1,
        'sequence_to' => 99,
        'date' => '2025-03-15',
    ],
];

foreach ($tokens as $rectification) {
    $safe->removeReportRunning();

    $retries = 1;
    $is_another_report_running = $safe->checkRunningReport($safe::TAMPER_TOKEN_RUNNING_REPORT, false);
    while ($is_another_report_running === true && $retries <= 5) {
        echo "Attempt #$retries. There is another report running. Retrying in 15 seconds... \n";
        sleep(15);
        $retries++;
        $is_another_report_running = $safe->checkRunningReport($safe::TAMPER_TOKEN_RUNNING_REPORT, false);
    }

    if ($is_another_report_running === true) {
        echo "Could not regenerate the reports.\n";
        echo "Exiting regeneration.\n";
        die(1);
    }

    $safe->extractParams();

    sleep(1);
    $sequenceArray = createSequenceArray($rectification['sequence_from'], $rectification['sequence_to']);
    $new_token = $dk->regenerateDataBySequence($sequenceArray, $rectification['token']);
    echo "The new token is $new_token.\n";
}

function createSequenceArray(int $sequenceFrom, int $sequenceTo): array
{
    $sequenceArray = [];

    for ($i = $sequenceFrom; $i <= $sequenceTo; $i++) {
        $sequenceArray[] = $i;
    }

    return $sequenceArray;
}
