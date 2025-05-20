<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

$sql = phive('SQL');

$sql->loopShardsSynced(function ($sql, $shard, $id){
    $sql->loadArray(
        "UPDATE sport_transaction_info sti
                JOIN sport_transactions st 
                ON sti.sport_transaction_id = st.id
                SET sti.ticket_id = st.ticket_id
                WHERE sti.ticket_id IS NULL;"
    );
    echo "\nshard $id updated\n";
});