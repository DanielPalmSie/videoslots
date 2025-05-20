<?php

/**
 * Create a test account.
 *
 * @param string $sc_id         Story Id used in logging and auditing
 * @param string $email         USED FOR USERNAME AND EMAIL
 * @param string $firstname     The first name of the new account
 * @param string $lastname      The last name of the new account
 * @param string $country       The country of the new account
 * @param string $language      The preferred language of the new account
 * @param string $currency      The currency language of the new account
 * @param string $password      The preferred language of the new account
 * @param string $province      Optional - used for Canada ON
 */
function createTestAccount($sc_id, $email, $firstname, $lastname, $country, $language, $currency, $password, $province = null)
{
    if (!empty(phive('UserHandler')->getUserByUsername($email, true))) {
        echo "WARN: User with username [{$email}] already exists - not creating\n";
        return;
    }

    echo "Creating Test User {$email} ... ";

    //prepare data
    $data = array();
    $data['email'] = "{$email}";
    $data['mobile'] = rand(35600000000, 35600009999);
    $data['country'] = $country;
    $data['newsletter'] = 0;
    $data['sex'] = "Male";
    $data['lastname'] = $lastname;
    $data['firstname'] = $firstname;
    $data['address'] = "Test Street " . rand(1, 50);
    $data['city'] = "Sta Venera Hills";
    $data['zipcode'] = "ABC777";
    $data['dob'] = "1990-09-" . rand(1, 28);
    $data['preferred_lang'] = $language;
    $data['username'] = $email;
    $data['password'] = phive('UserHandler')->encryptPassword($password);
    $data['bonus_code'] = "";
    $data['reg_ip'] = "";
    $data['friend'] = "";
    $data['alias'] = "";
    $data['cur_ip'] = "";
    $data['nid'] = "";
    $data['register_date'] = date('Y-m-d');
    $data['currency'] = $currency;

    //Add user to master
    $sql = phive('SQL');
    $user_id = $sql->insertArray('users', $data);

    if (empty(phive('UserHandler')->getUserByUsername($email, true))) {
        echo "ERROR: Failed creating user in master !!!!!\n";
        return;
    };

    //Add user to shard
    $data['id'] = $user_id;
    $sql->sh($user_id)->insertArray('users', $data);

    $user = cu($user_id);
    if (empty($user)) {
        echo "ERROR: Failed creating user in shard !!!!!\n";
        return;
    };

    //Set the test user settings
    $user->setSetting('test_account', 1);
    $user->setSetting("verified", 1, false);
    $user->setSetting("has_privacy_settings", 1, false);
    $user->setSetting("pp-version", '1.0', false);
    $user->setSetting("nationality", $country, false);
    if (!empty($province)) {
        $user->setSetting("main_province", $province, false);
    }
    echo "Created Id:[{$user->getId()}] UserName:[{$user->getUsername()}] Country:[{$user->getCountry()}] Currency:[{$user->getAttribute('currency')}] Email:[{$user->getAttribute('email')}], Active:[{$user->getAttribute('active')}] \n";
}

/**
 * Loops through a list and create test accounts on the specified env.
 *
 * @param string $sc_id         Story Id used in logging and auditing
 * @param string $full_csv_path The CSV file name (including full path) to be processed
 *                              CSV Columns: 'username', 'firstname', 'lastname', 'country', 'language', 'currency', 'password', ['province']
 */
function createTestAccountsCsv($sc_id, $full_csv_path)
{

    echo "Creating Test accounts ----------\n";
    $csv = readCsv($full_csv_path);
    $cnt = 0;
    foreach ($csv as $u) {
        createTestAccount($sc_id, $u['username'], $u['firstname'], $u['lastname'], $u['country'], $u['language'], $u['currency'], $u['password'], $u['province']);
        $cnt++;
    }
    echo "Created {$cnt} test users  ----------\n\n";
}
