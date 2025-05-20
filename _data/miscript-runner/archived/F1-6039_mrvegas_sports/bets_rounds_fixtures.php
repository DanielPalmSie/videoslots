<?php

use Illuminate\Support\Facades\DB;
use Videoslots\Sharded\Facades\DB as SHARD_DB;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

/* step1 Get rounds fixtures file content */
$filePath =  __DIR__ . '/roundsFixtures.json';
$jsonData = file_get_contents($filePath);

if ($jsonData === false) {
    echo "Failed to read the file";
} else {
    $decodedData = json_decode($jsonData, true);

    if ($decodedData === null) {
        echo "Failed to decode JSON data\n";
    } else {
        echo "Data successfully retrieved and decoded\n";
    }
}


/* step2 Get poolx bets from DB by necessary brand */
$poolxConnection = DB::connection('sportsbook_poolx');
$brand = 2; //MRV

$poolxBets =  $poolxConnection->table('poolx_bets as pb')
    ->select('pb.id', 'pb.round_id', 'user_id')
    ->where('pb.brand_id', '=', $brand)
    ->get()
    ->toArray();

if ($poolxBets) {
    echo "Data successfully retrieved from poolx_bets table\n";
} else {
    echo "Failed to get data from poolx_bets table\n";
}


/* step3 Map together and structurize poolx bets from DB and fixtures data from file */
$structuredArray = [];
foreach ($poolxBets as $bet) {
    if (isset($decodedData[$bet->round_id])) {
        /* Add the user_id to each event of round */
        foreach ($decodedData[$bet->round_id] as $key => $event) {
            $decodedData[$bet->round_id][$key]['user_id'] = $bet->user_id;
        }
        $structuredArray[$bet->id] = $decodedData[$bet->round_id];
    }
}

if (!empty($structuredArray)) {
    echo "Structured array successfully created\n";
} else {
    echo "Failed to create structurize array\n";
}


/* step4 Update sport_transaction_info table */
foreach ($structuredArray as $ticketId => $events) {
    $firstEvent = $events[0]; // first event usage to update already existing record which we have
    $existingRecord = SHARD_DB::shTable($firstEvent['user_id'], 'sport_transaction_info')
        ->where('ticket_id', $ticketId)
        ->where('network', 'poolx')
        ->whereNull('event_info')
        ->first();

    /* if record exists, update it with the data from the first event */
    if ($existingRecord) {
        if ($firstEvent['event_date']) {
            $eventDate = str_replace(['T', 'Z'], [' ', ''], $firstEvent['event_date']);
        }
        SHARD_DB::shTable($firstEvent['user_id'], 'sport_transaction_info')
            ->where('ticket_id', $ticketId)
            ->where('network', 'poolx')
            ->update([
                'user_id' => $firstEvent['user_id'],
                'event_date' => $eventDate ?? null,
                'event_type' => $firstEvent['event_type'] ?? null,
                'event_description' => $firstEvent['event_description'] ?? null,
                'bet_mode' => $firstEvent['bet_mode'] ?? null,
                'event_info' => $firstEvent['event_info'] ?? null,
            ]);

        /* Insert new records for the other events with the same ticket_id */
        $data = [];
        foreach ($events as $key => $event) {
            /* skip the first event since we already used it for updating */
            if ($key === 0) {
                continue;
            }

            if ($event['event_date']) {
                $eventDate = str_replace(['T', 'Z'], [' ', ''], $event['event_date']);
            }

            $data[] = [
                'user_id' => $firstEvent['user_id'],
                'ticket_id' => $ticketId,
                'event_date' => $eventDate ?? null,
                'event_type' => $event['event_type'] ?? null,
                'event_description' => $event['event_description'] ?? null,
                'bet_mode' => $event['bet_mode'] ?? null,
                'event_info' => $event['event_info'] ?? null,
                'network' => $existingRecord->network,  // If available
                'transaction_type' => $existingRecord->transaction_type, // If available
                'sport_transaction_id' => $existingRecord->sport_transaction_id, // Use sport_transaction_id from the existing record
                'json_data' => $existingRecord->json_data, // Use json_data from the existing record
            ];
        }

        if (!empty($data)) {
            SHARD_DB::shTable($firstEvent['user_id'], 'sport_transaction_info')->insert($data);
        }
    }
}

echo "Done, table data should be successfully created\n";
