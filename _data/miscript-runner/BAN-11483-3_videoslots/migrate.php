<?php

$sql = Phive("SQL");

const SUPPLIER = 'trustly';
const SUB_SUPPLIER = 'swish';
const DOCUMENT_TAG = 'bankaccountpic';
const PART_LIMIT = 1000;

$updatedDocumentIds = [];
$offset = 0;

do {
    echo "Fetching documents with offset {$offset} and limit " . PART_LIMIT . " ...\n";

    $documentsSql = "SELECT id, client_id, user_id, external_id, subtag
                   FROM documents
                   WHERE
                        client_id IN ('" . implode("','", CUSTOMER_IDS) . "')
                        AND tag = '" . DOCUMENT_TAG . "'
                        AND supplier = '" . SUPPLIER . "'
                        AND sub_supplier = '" . SUB_SUPPLIER . "'
                   ORDER BY id ASC
                   LIMIT {$offset}," . PART_LIMIT;

    $documents = $sql->doDb('dmapi')->loadArray($documentsSql);

    foreach ($documents as $document) {
        $accountExternalId = $document['subtag'];
        $account = getAccount($document['client_id'], $document['user_id'], $accountExternalId, $sql);

        if ($account) {
            continue;
        }

        $accountAttributes = [
            'customer_id' => $document['client_id'],
            'user_id' => $document['user_id'],
            'supplier' => SUPPLIER,
            'ext_id' => $accountExternalId,
            'sub_supplier' => SUB_SUPPLIER,
            'created_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            'updated_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            'verification_status' => 0,
            'data' => '',
        ];

        $accountsCreateSql = "
            INSERT INTO accounts (" . implode(',', array_keys($accountAttributes)) . ")
            VALUES ('" . implode("','", $accountAttributes) . "')";

        $created = $sql->doDb('mts')->query($accountsCreateSql);

        if (!$created) {
            echo "Account was not created for document {$document['id']}.\n";

            continue;
        }

        $account = getAccount($document['client_id'], $document['user_id'], $document['subtag'], $sql);

        $paymentAccountUpdateSql = "UPDATE payment_accounts
                                    SET
                                        account_id = '{$account['id']}'
                                    WHERE
                                        id = {$document['external_id']}";

        $paymentAccountUpdated = $sql->doDb('mts')->query($paymentAccountUpdateSql);

        if (!$paymentAccountUpdated) {
            echo "Payment account was not updated for {$document['id']}.\n";
        }

        $dmapiDocumentUpdateSql = "UPDATE documents
                                    SET
                                        account_ext_id = '{$accountExternalId}'
                                    WHERE
                                        id = {$document['id']}";

        $documentUpdated = $sql->doDb('dmapi')->query($dmapiDocumentUpdateSql);

        if (!$documentUpdated) {
            echo "Document was not updated {$document['id']}.\n";

            continue;
        }

        $updatedDocumentIds[] = $document['id'];
    }

    $offset += PART_LIMIT;

} while(count($documents) === PART_LIMIT);

echo "Total of " . count($updatedDocumentIds) . " documents have been updated successfully.\n";


function getAccount(int $customerId, string $userId, string $accountExternalId, $sql): ?array
{
    $selectAccounts = "SELECT a.id
                            FROM accounts a
                            WHERE
                                customer_id = {$customerId}
                                AND user_id = {$userId}
                                AND (supplier = '" . SUPPLIER . "' OR supplier = '" . SUB_SUPPLIER . "')
                                AND ext_id = '{$accountExternalId}'";

    $accounts = $sql->doDb('mts')->loadArray($selectAccounts);

    if ($accounts) {
        return reset($accounts);
    }

    return null;
}
