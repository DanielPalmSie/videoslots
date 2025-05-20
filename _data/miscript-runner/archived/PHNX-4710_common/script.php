<?php

use Videoslots\HistoryMessages\Exceptions\InvalidMessageDataException;
use Videoslots\HistoryMessages\UserUpdateHistoryMessage;

function sendUserUpdateHistoryMessage($users)
{
    $current_time = time();
    foreach ($users as $user) {
        $user_id = $user["user_id"];

        $user_object = cu($user_id);
        $user_data = $user_object->getData();

        $changes = array_map(function($data) {
            return ['from' => null, 'to' => $data];
        }, $user_data);

        try {
            $history_message = new UserUpdateHistoryMessage([
                'user_id'         => (int)$user_id,
                'changes'         => $changes,
                'event_timestamp' => $current_time,
            ]);

            $log_message = "Sent user_updated message to {$user_id}";
            if (lic('addRecordToHistory', ['user_updated', $history_message], $user_id)) {
                phive('DBUserHandler')->logAction($user_object, $log_message . " [PHNX-4710]", 'comment');
            } else {
                $log_message = "user_updated message failed for {$user_id}";
            }
            echo $log_message.PHP_EOL;
        } catch (InvalidMessageDataException $e) {
            echo "User {$user_id} {$e->getMessage()}" . PHP_EOL;
        }
    }
}
