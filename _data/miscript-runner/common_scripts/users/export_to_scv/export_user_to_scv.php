<?php

/**
 * Export a user from phive into SCV
 *
 * @param int $user_id The phive user id
 */
function exportUserToSCV(int $user_id)
{
    $user = cu($user_id);
    if (!$user) {
        $message = "ERROR: This user does not exist";
        echo $message;
        return ['success' => false, 'result' => $message];
    }

    $local_brand_to_remote_map = [
        'videoslots' => 'mrvegas',
        'mrvegas' => 'videoslots',
    ];

    $remote_brand = getRemote();
    $local_brand = getLocalBrand();
    $scv_link_setting = distKey($remote_brand);
    $old_brand_link_setting = distKey($local_brand_to_remote_map[$local_brand]);

    $status_initiated = DBUser::SCV_EXPORT_STATUS_VALUE_INITIATED;
    $status_completed = DBUser::SCV_EXPORT_STATUS_VALUE_COMPLETED;
    $status_scv_error = DBUser::SCV_EXPORT_STATUS_VALUE_SCV_ERROR;

    $sql = Phive('SQL');

    if ($user->hasSetting($scv_link_setting)) {
        $message = "ERROR: This user already has $scv_link_setting";
        echo $message;
        return ['success' => false, 'result' => $message];
    }

    $query = "
            SELECT users.id                       AS user_id,
                   users.country                  AS country,
                   registration_end_date.value    AS registration_end_date,
                   brand_link_user_id.value       AS brand_link_user_id
            FROM users
                LEFT JOIN users_settings registration_end_date
                    ON registration_end_date.user_id = users.id AND
                    registration_end_date.setting = 'registration_end_date'
                LEFT JOIN users_settings brand_link_user_id
                    ON brand_link_user_id.user_id = users.id AND
                    brand_link_user_id.setting = '{$old_brand_link_setting}'
            WHERE users.id = {$user_id}
        ";
    $user_details = $sql->sh($user_id)->loadAssoc($query);

    $sql->sh($user_id)->delete('scv_export_status', ['user_id' => $user_id]);

    $user_data = $user->getData();
    unset($user_data['id']);

    $jurisdiction = $user->getJurisdiction();

    $data = [
        'user' => $user_data,
        'users_settings' => [
            'remote_brand_tag' => $local_brand_to_remote_map[$local_brand],
            'remote_brand_user_id' => $user_details['brand_link_user_id'],
            'registration_end_date' => $user_details['registration_end_date'],
        ],
    ];

    $user->setOrUpdateSCVExportStatus($status_initiated);

    $response = toRemote(
        'scv',
        'importUserFromBrand',
        [$user_id, $jurisdiction, $data]
    );

    if ($response['success'] === true) {
        $customer_id = $response['result'];
        $user->setSetting($scv_link_setting, $customer_id);
        $user->setOrUpdateSCVExportStatus($status_completed);
        $message = "SUCCESS: Exported user {$user_id}. {$scv_link_setting} set to {$customer_id}";
        echo $message;
        return ['success' => true, 'result' => $message];
    }

    $user->setOrUpdateSCVExportStatus($status_scv_error);
    $message = "ERROR: Could not export {$user_id}.";
    echo $message;
    return ['success' => false, 'result' => $message];
}


/**
 * Gets all users with failed or incorrect-link statuses.
 *
 * @param string $country
 * @return mixed
 */
function getUsersToExportFailedAndIncorrectLink(string $country)
{
    $status_initiated = DBUser::SCV_EXPORT_STATUS_VALUE_INITIATED;
    $status_scv_error = DBUser::SCV_EXPORT_STATUS_VALUE_SCV_ERROR;
    $status_failed = DBUser::SCV_EXPORT_STATUS_VALUE_FAILED;
    $status_incorrect_link = DBUser::SCV_EXPORT_STATUS_VALUE_INCORRECT_LINK;

    $sql = phive("SQL");
    return $sql->shs()->loadArray("
        SELECT *
        FROM scv_export_status
        LEFT join users u on scv_export_status.user_id = u.id
        WHERE country = '{$country}'
          AND status IN (
            '{$status_initiated}',
            '{$status_scv_error}',
            '{$status_failed}',
            '{$status_incorrect_link}'
        ) AND user_id NOT IN (
            SELECT user_id FROM users_settings WHERE setting = 'c999_id'
        );
    ");
}

/**
 * Exports a batch of users to SCV.
 *
 * @param array $users_to_export
 * @return array
 */
function exportBatchToScv(array $users_to_export): array
{

    echo PHP_EOL . "Exporting remaining users to SCV" . PHP_EOL;

    $count = count($users_to_export);

    echo "Number of users to export: {$count}" . PHP_EOL;

    $to_save = [];
    foreach ($users_to_export as $key => $user) {
        $user_id = (int)$user['user_id'];
        $current = $key + 1;
        echo "{$current}/{$count}| user_id: {$user_id} | ";
        $result = exportUserToSCV($user_id);
        $user['success'] = $result['success'] ? 'Yes' : 'No';
        $user['result'] = $result['result'];
        $to_save[] = $user;
        echo PHP_EOL;
    }

    echo PHP_EOL . "Export finished" . PHP_EOL;

    return $to_save;
}
