<?php

require_once "{$root_folder_path}/phive/phive.php";
require_once "{$commons_folder}/common_functions.php";
require_once "{$commons_folder}/users/permissions/permissions.php";

$requester = "@andrej.marchanka";            # change requester
//$sc_id = 4467;           # enable to override story ID if different from folder name
$post_shortcut = true;       # enable posting of script output - set false if script produces secret output
$close_story = false;         # enable closing story - set false if same story requires multiple scripts to be run
$move_story_folder = false;   # enable moving story folder to archived folder - set false if not needed
$push_script_output = true;  # enable pushing story output to git - set false if not needed
$is_test = false;             # `true` will override and disable the 4 variables above - set `false` for production

$password = '666Megariches666'; //Update before running script

if (!isCli()) {
    die("Error: the script must be run in a CLI environment" . PHP_EOL);
}


$csv_path = __DIR__ . "/users.csv";
$users = readCsv($csv_path);

$i = 1;

foreach ($users as $u) {
    $username = "{$u['username']}";
    echo $username;
    $user_object = cu($username);
    if (empty($user_object)) {
        $i++;
        $data = array();
        $data['email'] = "pnp.{$username}@pnptest.com";
        $data['mobile'] = 3505550199 . $i;
        $data['country'] = $u['country'];
        $data['newsletter'] = 0;
        $data['sex'] = "Male";
        $data['lastname'] = $u['lastname'];
        $data['firstname'] = $u['firstname'];
        $data['address'] = "Test Street {$i}";
        $data['city'] = "Sta Venera Hills";
        $data['zipcode'] = "ABC777";
        $data['dob'] = "1990-09-{$i}";
        $data['preferred_lang'] = $u['language'];
        $data['username'] = $username;
        $data['password'] = phive('UserHandler')->encryptPassword($password);
        $data['bonus_code'] = "";
        $data['reg_ip'] = "127.0.0.5";
        $data['friend'] = "";
        $data['alias'] = "";
        $data['cur_ip'] = "127.0.1.{$i}";
        $data['nid'] = "SE1234567{$i}";
        $data['register_date'] = date('Y-m-d');
        $data['currency'] = $u['currency'];

        $user_id = phive('SQL')->insertArray('users', $data);
        $data['id'] = $user_id;
        phive('SQL')->sh($user_id)->insertArray('users', $data);
        $user = cu($user_id);
        $user->setSetting('test_account', 1);
        $user->setSetting('freeroll-tester', 1);
        $user->setSetting("verified", 1, false);
        $user->setSetting("tc-version", '2.10', false);
        $user->setSetting("pp-version", '1.0', false);
        $user->setSetting("has_privacy_settings", 1, false);

        $permissions = phive('UserHandler/PRUserHandler');

        $pdata_login = [
            'user_id' => $user_id,
            'tag' => 'account.pnp.login',
            'permission' => 'grant'
        ];

        $pdata_top = [
            'user_id' => $user_id,
            'tag' => 'admin_top',
            'permission' => 'grant'
        ];

        $permissions->addPermissionTagUser($pdata_login);
        $permissions->addPermissionTagUser($pdata_top);

        echo "{$username} - {$user_id} OK\n";
    } else {
        echo "{$username} - {$user_object->getAttribute('id')} NOT OK\n";
    }
}
