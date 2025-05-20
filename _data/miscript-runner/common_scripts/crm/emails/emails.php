<?php

/**
 * Update Email Config from csv, typically runs at last day of the month.
 *
 * @param string $sc_id         Story Id used in logging and auditing
 * @param string $full_csv_path The CSV file name (including full path) to be processed
 *                              CSV Columns: id,[config_name],[config_tag],config_value,[OLD config_value]
 */
function updateEmailConfigFromCsv($sc_id, $full_csv_path)
{
    echo "CRM Email Schedule \n";

    $i = 0;
    $data = readCsv($full_csv_path);
    $sql = Phive('SQL');
    foreach ($data as $row) {
        echo " Processing config id {$row['id']} to [{$row['config_value']}] - ";

        $qry = "SELECT config_value FROM config WHERE id = {$row['id']}";
        $new_val = $row['config_value'];
        $old_val = $sql->loadAssoc($qry)['config_value'];
        if ($old_val == $new_val) {
            echo "value already set to {$new_val} \n";
        } else {
            $sql->shs()->query("UPDATE config SET config.config_value = '{$new_val}' WHERE id = {$row['id']}");
            $updated_val = $sql->loadAssoc($qry)['config_value'];
            echo "value updated from [{$old_val}] to [{$updated_val}] \n";
            $i++;
        }
    }

    echo "DONE {$i} rows updated ----- \n";
}

/**
 * Manual Email Send Out from csv, used for CRM promotions that are not included in the monthly Schedule,
 *
 * @param string $sc_id         Story Id used in logging and auditing
 * @param string $full_csv_path The CSV file name (including full path) to be processed
 *                              CSV Columns: user_id
 * @param string $lang          Language of the email - [PROVIDED IN THE TICKET]
 * @param string $mail_trigger  Trigger of email template - [PROVIDED IN THE TICKET]
 */
function manualEmailSendOutFromCsv($sc_id, $full_csv_path,$lang,$mail_trigger)
{
    $sql = Phive("SQL");
    if (!isCli()) {
        die("Error: the script must be run in a CLI environment" . PHP_EOL);
    }
    $test = false;
    $mh = phive('MailHandler2')->setFrom();
    $mh->unsubscribe_link = true;
    $csv = readCsv($full_csv_path);
    $users_to_send_to = $csv;
    $users_to_send_to_count = count($users_to_send_to);

    echo "{$sc_id} - Starting sendout to {$users_to_send_to_count} customers\n";
    $total_successful = 0;
    foreach ($users_to_send_to as $user) {

        $mod = $total_successful % 100;;
        if ($mod == 0) {
            usleep(100);
        }
        $user_id = $user['user_id'];
        $u = cu($user_id);

        if (empty($u)) {
            echo "{$user_id} Not Found\n";
            continue;
        }

        $res = false;
        if (!$test) {
            $res = $mh->sendMail($mail_trigger, $u, null, $lang, $mh->from, $mh->from, null, null, 3);
            phive('UserHandler')->logAction($user, "System sent mail with trigger: {$mail_trigger} - {$sc_id}", "mail_reminders", false);
        }

        if ($res !== false) {
            $total_successful++;
            echo "+";
        } else {
            echo "-";
        }
    }
    echo "\nDONE, mails sent successfully: $total_successful\n";
    $missed = $users_to_send_to_count - $total_successful;
    echo "Missed: {$missed}\n";
}
