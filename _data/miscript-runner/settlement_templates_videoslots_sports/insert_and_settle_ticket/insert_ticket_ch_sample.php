<?php

use Illuminate\Support\Facades\Log;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

$sportTransactionRepository = new \App\Sportsbook\Sts\Repositories\ManualSettlement\SportTransactionRepositoryUnsettledBets(app(\App\Sportsbook\Sts\Repositories\SportTransactionDetailsRepository::class));
$ticketCashierNoBalanceChange = new \App\Sportsbook\Sts\Repositories\ManualSettlement\TicketCashierNoBalanceChange($sportTransactionRepository, app(\App\Repositories\RgLimitRepository::class));

$ticketIds = [ticket_id1, ticket_id2]; //TODO: replace ticket ids

$tickets = \App\Sportsbook\Sts\Models\Ticket::findMany($ticketIds);

foreach ($tickets as $ticket) {
    Log::info("Inserting ticket {$ticket->id}...");

    $ticketCashierNoBalanceChange->insertBet($ticket, cu($ticket->user_id));

    Log::info("{$ticket->id} inserted");
}

Log::info("Processing completed");
