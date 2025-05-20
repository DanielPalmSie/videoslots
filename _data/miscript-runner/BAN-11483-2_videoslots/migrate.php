<?php

$sql = Phive("SQL");

const SUPPLIER = 'swish';
const SUB_SUPPLIER = 'trustly';
const TABLES = ['documents', 'deposits'];

$tablesOldestDates = getTablesOldestDate($sql);

if (!$tablesOldestDates) {
    echo "No data needs to be updated. we're good!\n";
    exit;
}

$oldestDate = min($tablesOldestDates);
$endDate = new DateTime();
$currentDate = $oldestDate;

while ($currentDate <= $endDate) {
    echo "==== " . $currentDate->format('Y-m-d') . " =======================\n";
    echo sprintf("%-20s %-10s %-10s\n", '', 'Updated', 'Failed');

    foreach (TABLES as $table) {

        if (
            !isset($tablesOldestDates[$table]) ||
            $tablesOldestDates[$table]->format('Y-m-d') > $currentDate->format('Y-m-d')
        ) {
            echo sprintf("%-20s %-10s\n", $table, 'N/A');
            continue;
        }

        $updateFunction = 'update' . ucfirst($table);
        $updateFunction($sql, $currentDate, $table);
    }

    $currentDate->modify('+1 day');
}

function getTablesOldestDate($sql): array
{
    $tablesOldestDates = [];

    if ($documentsOldestDate = getDocumentsTableOldestDate($sql)) {
        $tablesOldestDates['documents'] = $documentsOldestDate;
    }

    if ($depositsOldestDate = getDepositsTableOldestDate($sql)) {
        $tablesOldestDates['deposits'] = $depositsOldestDate;
    }

    return $tablesOldestDates;
}

function getDocumentsTableOldestDate($sql): ?DateTime
{
    $selectSql =   "SELECT created_at
                    FROM documents
                    WHERE
                        client_id = " . CUSTOMER_ID . " AND
                        tag = 'bankaccountpic' AND
                        (
                            (supplier = '" . SUPPLIER . "' AND sub_supplier = '') OR
                            (supplier = '" . SUB_SUPPLIER . "' AND sub_supplier = '" . SUPPLIER."')
                        )
                    ORDER BY created_at ASC
                    LIMIT 1";

    $result = $sql->doDb('dmapi')->loadAssoc($selectSql);

    if ($result) {
        return new DateTime($result['created_at']);
    }

    return null;
}

function getDepositsTableOldestDate($sql): ?DateTime
{
    $shardsOldestDate = [];

    $selectSql =   "SELECT timestamp
                    FROM deposits
                    WHERE
                        (dep_type = '" . SUPPLIER . "' AND scheme = '') OR
                        (dep_type = '" . SUB_SUPPLIER . "' AND scheme = '" . SUPPLIER."')
                    ORDER BY timestamp ASC
                    LIMIT 1";

    if ($sql->isSharded('deposits')) {
        $sql->loopShardsSynced(function ($sql, $shard, $id) use (&$shardsOldestDate, $selectSql) {
            $result = $sql->sh($id)->loadAssoc($selectSql);

            if ($result) {
                $shardsOldestDate[] = new DateTime($result['timestamp']);
            }
        });
    } else {
        $result = $sql->loadAssoc($selectSql);

        if ($result) {
            $shardsOldestDate[] = new DateTime($result['timestamp']);
        }
    }

    if (!$shardsOldestDate) {
        return null;
    }

    return min($shardsOldestDate);
}

function updateDocuments($sql, DateTime $currentDate, string $table)
{
    $nextDate = clone $currentDate;
    $nextDate->modify('+1 day');

    $updatedRecords = [];
    $failedRecords = [];

    $selectSql =   "SELECT id
                    FROM $table
                    WHERE
                        created_at >= '" . $currentDate->format('Y-m-d') . "' AND
                        created_at < '" . $nextDate->format('Y-m-d') . "' AND
                        client_id = " . CUSTOMER_ID . " AND
                        tag = 'bankaccountpic' AND
                        (
                            (supplier = '" . SUPPLIER . "' AND sub_supplier = '') OR
                            (supplier = '" . SUB_SUPPLIER . "' AND sub_supplier = '" . SUPPLIER."')
                        )";

    $records = $sql->doDb('dmapi')->loadArray($selectSql);

    foreach ($records as $record) {
        $updateSql =   "UPDATE $table
                        SET
                            supplier = '" . SUPPLIER . "',
                            sub_supplier = '" . SUB_SUPPLIER . "'
                        WHERE
                            id = {$record['id']}";

        if ($sql->doDb('dmapi')->query($updateSql)) {
            $updatedRecords[] = $record['id'];
        } else {
            $failedRecords[] = $record['id'];
        }
    }

    echo sprintf("%-20s %-10s %-10s\n", $table, count($updatedRecords), count($failedRecords));

    if ($failedRecords) {
        echo "$table Failed IDs: " . join(',', $failedRecords) . "\n";
    }
}

function updateDeposits($sql, DateTime $currentDate, string $table)
{
    $nextDate = clone $currentDate;
    $nextDate->modify('+1 day');

    if ($sql->isSharded('deposits')) {
        $sql->loopShardsSynced(function ($sql, $shard, $id) use ($currentDate, $nextDate, $table) {
            updateDepositsByShard($sql, $id, $currentDate, $nextDate, $table);
        });
    } else {
        updateDepositsByShard($sql, null, $currentDate, $nextDate, $table);
    }
}

function updateDepositsByShard($sql, $shardId, $currentDate, $nextDate, $table)
{
    $updatedRecords = [];
    $failedRecords = [];

    $selectSql =   "SELECT id
                        FROM $table
                        WHERE
                            timestamp >= '" . $currentDate->format('Y-m-d') . "' AND
                            timestamp < '" . $nextDate->format('Y-m-d') . "' AND
                            (
                                (dep_type = '" . SUPPLIER . "' AND scheme = '') OR
                                (dep_type = '" . SUB_SUPPLIER . "' AND scheme = '" . SUPPLIER . "')
                            )";

    if ($shardId) {
        $records = $sql->sh($shardId)->loadArray($selectSql);
    } else {
        $records = $sql->loadArray($selectSql);
    }

    foreach ($records as $record) {
        $updateSql =   "UPDATE $table
                            SET
                                dep_type = '" . SUPPLIER . "',
                                scheme = '" . SUB_SUPPLIER . "'
                            WHERE
                                id = {$record['id']}";

        if ($shardId) {
            $depositUpdated = $sql->sh($shardId)->query($updateSql);
        } else {
            $depositUpdated = $sql->query($updateSql);
        }

        if ($depositUpdated) {
            $updatedRecords[] = $record['id'];
        } else {
            $failedRecords[] = $record['id'];
        }
    }

    echo sprintf("%-20s %-10s %-10s\n", "$table$shardId", count($updatedRecords), count($failedRecords));

    if ($failedRecords) {
        echo "$table$shardId Failed IDs: " . join(',', $failedRecords) . "\n";
    }
}
