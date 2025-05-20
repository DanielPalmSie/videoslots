<?php

require_once "{$root_folder_path}/phive/phive.php";
require_once "{$commons_folder}/common_functions.php";

$requester = "@petronelmorosanu";            # change requester
# $sc_id = 123456;           # enable to override story ID if different from folder name
$post_shortcut = true;       # enable posting of script output - set false if script produces secret output
$close_story = true;         # enable closing story - set false if same story requires multiple scripts to be run
$move_story_folder = true;   # enable moving story folder to archived folder - set false if not needed
$push_script_output = true;  # enable pushing story output to git - set false if not needed
$is_test = false;             # 'true' will override and disable the 4 variables above - set 'false' for production
$create_lockfile = true;     # handles creation and pushing of lockfile if set to true
# $extra_args can store additional parameter supplied from the pipeline

$sql = Phive("SQL");
if (!isCli()) {
    die("Error: the script must be run in a CLI environment" . PHP_EOL);
}

echo " Closing affiliate accounts on MRV - DEVS-16031\n";
$users_by_username = ['wilaqu'];
foreach ($users_by_username as $user) {
    $query_user_id = "
                SELECT id, company_id
                FROM users
                WHERE username = '{$user}';
                ";
    $result_user_id = phive('SQL')->loadArray($query_user_id);
    echo "Users to be blocked: {$result_user_id[0]['id']}  company id: {$result_user_id[0]['company_id']}\n";

    if (!empty($result_user_id) and count($result_user_id) == 1) {
        // Blocking future payments
        $query_block_payout = "
            UPDATE companies
            SET companies.min_payout = 1000000000
            WHERE company_id = {$result_user_id[0]['company_id']};
        ";
        $result_block_payout = phive('SQL')->query($query_block_payout);
        echo $result_user_id[0]['company_id'] . " payout block ";
        echo($result_block_payout ? "ok" : "not ok");
        echo " \n";
        // Block affiliates
        $query_block_user = "
            UPDATE users
            SET username = CONCAT(username, '_blocked'),
                accepted = '0'
            WHERE id = {$result_user_id[0]['id']};
        ";
        $res_block_user = phive("SQL")->query($query_block_user);
        echo $result_user_id[0]['id'] . " user block ";
        echo($res_block_user ? "ok" : "not ok");
        echo " \n";

        // Remove affiliate from groups
        $query_delete_member ="
        DELETE FROM groups_members
        WHERE user_id = {$result_user_id[0]['id']};
        ";
        $query_delete_member = phive("SQL")->query($query_delete_member);
        echo $result_user_id[0]['id'] . " group member delete ";
        echo($query_delete_member ? "ok" : "not ok");
        echo " \n";
    }
}

echo "DONE - sc-{$sc_id}\n";
exit;
