<?php

use App\SportsbookAltenar\Constants\BetModes;
use App\SportsbookAltenar\Constants\BetStatuses;
use App\SportsbookAltenar\Constants\TransactionTypes;
use App\SportsbookAltenar\Helpers\Common;
use App\SportsbookAltenar\Repositories\TransactionRepository;
use App\SportsbookAltenar\Services\BetService;
use App\SportsbookAltenar\Services\TransactionService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB as Sts;
use Videoslots\Sharded\Facades\DB as ShardedDB;

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle($request = Illuminate\Http\Request::capture());

$ticketId = 136884;
$userId = 879329;
$brandId = 2;
$extTransactionId = 74738879909;
$altExtTransactionId = 747388799110;
$betReferenceNum = 3530702078;

$betService = App::make(BetService::class);
$transactionService = App::make(TransactionService::class);
$transactionRepository = App::make(TransactionRepository::class);

logInfo("F1-6068: Starting script");
logBalance("Previous", $userId);
logInfo("F1-6068: Inserting altenar_bets and altenar_transactions WITHOUT balance change for ticket ID {$ticketId}");

try {
    Sts::beginTransaction();

    $betService->update($ticketId, [
        'ext_bet_status'    => BetStatuses::VOID,
        'status'              => BetStatuses::TYPE_VOID,
    ]);

    $bet = $betService->findById($ticketId);
    $transactionDTO = $transactionService->findByBetRefAndExtTransactionId($extTransactionId, $betReferenceNum);
    $transaction = $transactionDTO->getDetails()['transaction'][0];
    $params = [
        'user_id'                           => $transactionDTO->getUserId(),
        'brand_id'                          => $brandId,
        'ext_transaction_id'                => $altExtTransactionId,
        'reference_number'                  => $betReferenceNum,
        'type'                              => BetStatuses::TYPE_VOID,
        'transaction_type'                  => TransactionTypes::REFUND_BET,
        'bet_mode'                          => BetModes::LIVE_EVENTS,
        'bet_status'                        => BetStatuses::VOID,
        'description'                       => $transaction['description'],
        'amount'                            => $transactionDTO->getAmount(),
        'currency'                          => $transaction['currency'],
        'game_reference'                    => $transaction['game_reference'],
        'user_ip'                           => $transaction['user_ip'],
        'additional_transaction_info_json'  => $transaction['additional_transaction_info_json']
    ];
    $transactionRepository->persistByBet($bet, $params);
    Sts::commit();

    logInfo("F1-6068: Successfully aligned records into sts for ticket ID {$ticketId}");
} catch (Throwable $e) {
    Sts::rollBack();
    logError("F1-6068: Error inserting records into sts for ticket ID {$ticketId}. {$e->getMessage()}");
    logBalance("New", $userId);
    exit;
}

logBalance("New", $userId);

try {
    $transactionDTO = $transactionService->findByBetRefAndExtTransactionId($altExtTransactionId, $betReferenceNum);
    $transactionDetails = $transactionDTO->getDetails();
    $betSportTransaction = $transactionService->getSportsTransactionByTicketId($userId, $ticketId, BetStatuses::TYPE_BET);
    $altenarCreatedTransaction = $transactionDetails['transaction'][1];

    $params = [
        'ticket_type'       => $betSportTransaction->ticket_type,
        'type'              => BetStatuses::TYPE_VOID,
        'brand_id'          => $brandId,
        'user_id'           => $userId,
        'ext_id'            => $altExtTransactionId,
        'ticket_id'         => $ticketId,
        'currency'          => $altenarCreatedTransaction['currency'],
        'balance'           => cu($userId)->data['cash_balance'],
        'amount'            => $betSportTransaction->amount,
        'transaction_type'  => TransactionTypes::REFUND_BET,
    ];

    logInfo("F1-6068: Inserting sport_transaction and sport_transaction_info WITHOUT balance change for ticket ID {$ticketId}");
    logBalance("Previous", $userId);


    ShardedDB::shBeginTransaction();

    $sportTransaction = $transactionService->persistOrUpdateSportTransaction(
        $altenarCreatedTransaction,
        $ticketId,
        $params,
        BetStatuses::TYPE_VOID
    );

    $transactionService->persistSportTransactionInfo(
        $userId,
        $sportTransaction->id,
        $params,
        $ticketId,
        config('networks.ALTENAR.name', 'altenar')
    );

    ShardedDB::shCommit();

    logInfo("F1-6068: Successfully aligned records into shards for ticket ID {$ticketId}");
} catch (Throwable $e) {
    ShardedDB::shRollback();
    logError("F1-6068: Error inserting records into sharded DB for ticket ID {$ticketId}. {$e->getMessage()}");
    logBalance("New", $userId);
    exit;
}

logBalance("New", $userId);
logInfo("F1-3980: Completed");

function logInfo(string $message): void
{
    Common::logData($message);
}

function logError(string $message): void
{
    Common::logData($message, [], 'error');
}

function logBalance(string $label, int $userId): void
{
    $balance = cu($userId)->data['cash_balance'];
    Common::logData("F1-6068: {$label} user balance = {$balance}");
}
