<?php
require_once "{$root_folder_path}/phive/phive.php";
require_once "{$commons_folder}/common_functions.php";


$requester = "@mihailo.ilic";            # change requester
// $sc_id = 123;       // enable to override story Id if different from folder name
$post_shortcut = true;       # enable posting of script output - set false if script produces secret output
$close_story = true;         # enable closing story - set false if same story requires multiple scripts to be run
$move_story_folder = true;  # enable moving story folder to archived folder - set false if not needed
$push_script_output = true;  # enable pushing story output to git - set false if not needed
$create_lockfile = true;     # handles creation and pushing of lockfile if set to true
$is_test = false;             # 'true' will override and disable the 4 variables above - set 'false' for production

$sql = Phive("SQL");
if (!isCli()) {
    die("Error: the script must be run in a CLI environment" . PHP_EOL);
}


$password = '?2critical4me321?';
$password_encrypted = phive('UserHandler')->encryptPassword($password);
$users = phive("SQL")->loadArray("
    SELECT * FROM users
    WHERE email IN ('techsupnz@mrvegas.com','techsupat@mrvegas.com','techsuphu@mrvegas.com','techsupse@mrvegas.com','techsupmt@mrvegas.com','techsupnl@mrvegas.com','techsupfi@mrvegas.com','techsupjp@mrvegas.com','techsupde@mrvegas.com','techsupca@mrvegas.com','techsupin@mrvegas.com','techsupno@mrvegas.com','techsupru@mrvegas.com','techsupgb@mrvegas.com','techsupdk@mrvegas.com','techsupes@mrvegas.com','techsupit@mrvegas.com','devsupport@videoslots.com','techsupcaon@mrvegas.com')"
);

foreach ($users as $u) {
    $update_master = phive('SQL')->updateArray('users', ['password' => $password_encrypted], ['id' => $u['id']]);
    $update_shard = phive('SQL')->sh($u['id'])->updateArray('users', ['password' => $password_encrypted], ['id' => $u['id']]);
    echo "Changing password for {$u['username']}, id: {$u['id']} \r\n";
}
$out_text = "Password has been changed, new hash: $password_encrypted \r\n";
echo $out_text;
