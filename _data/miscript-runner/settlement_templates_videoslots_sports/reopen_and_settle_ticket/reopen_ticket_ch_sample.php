<?php

use Illuminate\Support\Facades\Log;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

//tickets to be settled  TODO: Replace with tickets to reopen
$ticketIds = [ticket_id1, ticket_id2];

$sportTransactionRepository = new \App\Sportsbook\Sts\Repositories\ManualSettlement\SportTransactionRepositoryUnsettledBets(app(\App\Sportsbook\Sts\Repositories\SportTransactionDetailsRepository::class));
$ticketCashier = new App\Sportsbook\Sts\Services\TicketCashier($sportTransactionRepository, app(\App\Repositories\RgLimitRepository::class));

$tickets = \App\Sportsbook\Sts\Models\Ticket::findMany($ticketIds);

foreach ($tickets as $ticket) {
    Log::info("Re-opening ticket {$ticket->id}...");
    $ticketCashier->reopenTicket($ticket, cu($ticket->user_id));
    Log::info("{$ticket->id} Reopened");
}

Log::info("All tickets Reopened..");
