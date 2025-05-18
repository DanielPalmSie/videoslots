<?php
require_once __DIR__ . '/../CashierNotify.php';

$notify_handler = new CashierNotify();

$res = $notify_handler->init();
if($res !== true){
    $notify_handler->stop($res);
}

$args = $notify_handler->getBase64Body($_POST);
$type = $args['extra']['type'] ?? 'unknown';

switch ($type) {
    case 'deposit':
        require_once __DIR__ . '/../DepositNotify.php';
        $handler = new DepositNotify();
        break;
    case 'withdrawal':
        require_once __DIR__ . '/../WithdrawNotify.php';
        $handler = new WithdrawNotify();
        break;
    default:
        phive('Logger')
            ->getLogger('payments')
            ->notice("Type not supported", [
                'input' => $_POST,
                'args' => $args,
                'type' => $type
            ]);

        $notify_handler->stop($notify_handler->success('Type not supported, skipped!'));
}

$res = $handler->transactionInit($args);
if($res === true){
    $res = $handler->execute($args);
}

$notify_handler->stop($res);
