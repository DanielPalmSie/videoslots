<?php

$sql = Phive("SQL");

const INSTADEBIT = 'instadebit';
const NEOSURF = 'neosurf';

echo "Updating Instadebit accounts ext_id...\n";

$accountsSql = "SELECT id, data
                FROM accounts
                WHERE
                     supplier = '" . INSTADEBIT . "' AND
                     user_id = ext_id";

$accounts = $sql->doDb('mts')->loadArray($accountsSql);

foreach ($accounts as $account) {
    $accountData = json_decode($account['data']);

    $accountUpdateSql =    "UPDATE accounts
                            SET
                                ext_id = '$accountData->user_id'
                            WHERE
                                id = {$account['id']}";

    $accountUpdated = $sql->doDb('mts')->query($accountUpdateSql);

    echo "Account {$account['id']} " . ($accountUpdated ? 'updated successfully.' : 'failed to update!') . "\n";
}

echo "Removing Neosurf accounts...\n";

$deleteAccountsSql =   "DELETE FROM accounts
	                    WHERE supplier = '" . NEOSURF . "'";

$accountsDeleted = $sql->doDb('mts')->query($deleteAccountsSql);

echo "Neosurf Accounts were " . ($accountsDeleted ? 'deleted successfully.' : 'failed to delete!') . "\n";
