<?php

require_once __DIR__ . '/../CashierNotify.php';

$notify_handler = new CashierNotify();

$res = $notify_handler->init();
if($res !== true){
    $notify_handler->stop($res);
}

$args = $notify_handler->getBase64Body($_POST);
$res = $notify_handler->executeFail($args);

$notify_handler->stop($res);
