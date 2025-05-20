<?php

/**
 * Add a setting from a user to a particular value.
 *
 * @param string  $sc_id   Story Id used in logging and auditing
 * @param integer $user_id The user id to add setting
 * @param string  $setting The name of the setting to add
 * @param string  $value   The value of the setting
 */
function addSetting($sc_id, $user_id, $setting, $value)
{
    $user = cu($user_id);
    if (empty($user)) {
        echo "ERROR: user with id {$user_id} not found in database!!\n";
        return;
    }

    if ($user->getSetting($setting) == $value) {
        echo "User {$user->getId()}: already has {$setting} setting: [{$user->getSetting($setting)}].\n";
    } else {
        $user->setSetting($setting, $value);
        phive('UserHandler')->logAction($user, "Added manually {$setting} setting - {$sc_id}", "comment");
        echo "User {$user->getId()}: Added {$setting}: [{$user->getSetting($setting)}].\n";
    }
}

/**
 * @param $sc_id
 * @param $user_id
 * @param $settings Array k - v, p.e: ['active' => 1, 'verified_nid' => 1]
 * @return void
 */
function addSettings($sc_id, $user_id, array $settings) {
    array_map(function($k, $v) use ($sc_id, $user_id) {
        addSetting($sc_id, $user_id, $k, $v);
    },array_keys($settings), array_values($settings));
}

/**
 * Adds a setting to multiple users.
 *
 * @param string $sc_id    Story Id used in logging and auditing
 * @param array  $user_ids The array of user ids to add setting
 * @param string $setting  The setting to add
 * @param string  $value   The value of the setting
 */
function addSettingMulti($sc_id, $user_ids, $setting, $value)
{
    echo "Adding setting {$setting} to multiple users -----\n";
    foreach ($user_ids as $user_id) {
        addSetting($sc_id, $user_id, $setting, $value);
    }
    echo "Processed all users -----\n";
}

/**
 * Set a setting to a user with value 1.
 *
 * @param string  $sc_id   Story Id used in logging and auditing
 * @param integer $user_id The user id to add setting
 * @param string  $setting The setting to add
 */
function setSetting($sc_id, $user_id, $setting)
{
    addSetting($sc_id, $user_id, $setting, 1);
}

/**
 * Set a setting to multiple users with value 1.
 *
 * @param string $sc_id    Story Id used in logging and auditing
 * @param array  $user_ids The array of user ids to add setting
 * @param string $setting  The setting to add
 */
function setSettingMulti($sc_id, $user_ids, $setting)
{
    addSettingMulti($sc_id, $user_ids, $setting, 1);
}

/**
 * Removes a setting from a user.
 *
 * @param string  $sc_id   Story Id used in logging and auditing
 * @param integer $user_id The user id to remove setting
 * @param string  $setting The setting to remove
 */
function removeSetting($sc_id, $user_id, $setting)
{
    if ($setting == 'cross_brand_check_block') {
        echo "ERROR: Use removeCrossBrandCheckBlock function to remove cross_brand!!\n";
        return;
    }

    $user = cu($user_id);
    if (empty($user)) {
        echo "ERROR: user with id {$user_id} not found in database!!\n";
        return;
    }

    if ($user->hasSetting($setting)) {
        $user->deleteSetting($setting);
        phive('UserHandler')->logAction($user, "Removed manually {$setting} setting - {$sc_id}", "comment");
        echo "User {$user->getId()}: Removed {$setting}: [{$user->getSetting($setting)}].\n";
    } else {
        echo "User {$user->getId()}: does not have {$setting}: [{$user->getSetting($setting)}].\n";
    }
}

/**
 * Removes a setting from multiple users.
 *
 * @param string $sc_id    Story Id used in logging and auditing
 * @param array  $user_ids The array of user ids to remove setting
 * @param string $setting  The setting to remove
 */
function removeSettingMulti($sc_id, $user_ids, $setting)
{
    echo "Remove setting {$setting} from multiple users -----\n";
    foreach ($user_ids as $user_id) {
        removeSetting($sc_id, $user_id, $setting);
    }
    echo "Processed all users -----\n";
}


/**
 * Add a withdrawal_block from a user.
 *
 * @param string $sc_id   Story Id used in logging and auditing
 * @param        $user_id The user id to add block
 */
function addWithdrawalBlock($sc_id, $user_id)
{
    setSetting($sc_id, $user_id, 'withdrawal_block');
}

/**
 * Adds a withdrawal_block from multiple users.
 *
 * @param string $sc_id    Story Id used in logging and auditing
 * @param        $user_ids The array of user ids to add block
 */
function addWithdrawalBlockMulti($sc_id, $user_ids)
{
    setSettingMulti($sc_id, $user_ids, 'withdrawal_block');
}

/**
 * Removes a withdrawal_block from a user.
 *
 * @param string $sc_id   Story Id used in logging and auditing
 * @param        $user_id The user id to remove block
 */
function removeWithdrawalBlock($sc_id, $user_id)
{
    removeSetting($sc_id, $user_id, 'withdrawal_block');
}

/**
 * Removes a withdrawal_block from multiple users.
 *
 * @param string $sc_id    Story Id used in logging and auditing
 * @param        $user_ids The array of user ids to remove block
 */
function removeWithdrawalBlockMulti($sc_id, $user_ids)
{
    removeSettingMulti($sc_id, $user_ids, 'withdrawal_block');
}



/**
 * Add a deposit_block from a user.
 *
 * @param string $sc_id   Story Id used in logging and auditing
 * @param        $user_id The user id to add block
 */
function addDepositBlock($sc_id, $user_id)
{
    setSetting($sc_id, $user_id, 'deposit_block');
}

/**
 * Adds a deposit_block from multiple users.
 *
 * @param string $sc_id    Story Id used in logging and auditing
 * @param        $user_ids The array of user ids to add block
 */
function addDepositBlockMulti($sc_id, $user_ids)
{
    setSettingMulti($sc_id, $user_ids, 'deposit_block');
}

/**
 * Removes a deposit_block from a user.
 *
 * @param string $sc_id   Story Id used in logging and auditing
 * @param        $user_id The user id to remove block
 */
function removeDepositBlock($sc_id, $user_id)
{
    removeSetting($sc_id, $user_id, 'deposit_block');
}

/**
 * Removes a deposit_block from multiple users.
 *
 * @param string $sc_id    Story Id used in logging and auditing
 * @param        $user_ids The array of user ids to remove block
 */
function removeDepositBlockMulti($sc_id, $user_ids)
{
    removeSettingMulti($sc_id, $user_ids, 'deposit_block');
}



/**
 * Add a play_block to a user.
 *
 * @param string $sc_id   Story Id used in logging and auditing
 * @param        $user_id The user id to add block
 */
function addPlayBlock($sc_id, $user_id)
{
    setSetting($sc_id, $user_id, 'play_block');
}

/**
 * Add a play_block from multiple users.
 *
 * @param string $sc_id    Story Id used in logging and auditing
 * @param array  $user_ids The array of user ids to add block
 */
function addPlayBlockMulti($sc_id, $user_ids)
{
    setSettingMulti($sc_id, $user_ids, 'play_block');
}

/**
 * Removes a play_block from a user.
 *
 * @param string $sc_id   Story Id used in logging and auditing
 * @param array  $user_id The user id to remove block
 */
function removePlayBlock($sc_id, $user_id)
{
    removeSetting($sc_id, $user_id, 'play_block');
}

/**
 * Removes a play_block from multiple users.
 *
 * @param string $sc_id    Story Id used in logging and auditing
 * @param        $user_ids The array of user ids to remove block
 */
function removePlayBlockMulti($sc_id, $user_ids)
{
    removeSettingMulti($sc_id, $user_ids, 'play_block');
}

/**
 * Removes a cross_brand_check_block from a user which can stop the user to deposit.
 *
 * @param string $sc_id   Story Id used in logging and auditing
 * @param        $user_id The user id to remove block
 */
function removeCrossBrandCheckBlock($sc_id, $user_id)
{
    $user = cu($user_id);
    if (empty($user)) {
        echo "ERROR: user with id {$user_id} not found in database!!\n";
        return;
    }

    if ($user->getSetting('cross_brand_check_block') == 1) {
        $cross_brand = licSetting('cross_brand', $user_id);
        phive('Site/Linker')->brandLink($user_id, empty($cross_brand['check_self_exclusion']) ? 'no' : 'yes');
        phive('UserHandler')->logAction($user, "Removed manually cross_brand_check_block setting - {$sc_id}", "comment");
        echo "User {$user->getId()}: Removed cross_brand_check_block: [{$user->getSetting('cross_brand_check_block')}].\n";
    } else {
        echo "User {$user->getId()}: does not have cross_brand_check_block: [{$user->getSetting('cross_brand_check_block')}].\n";
    }
}

/**
 * Removes a cross_brand_check_block from multiple users which can stop the users to deposit.
 *
 * @param string $sc_id    Story Id used in logging and auditing
 * @param        $user_ids The array of user ids to remove block
 */
function removeCrossBrandCheckBlockMulti($sc_id, $user_ids)
{
    echo "Remove block cross_brand_check_block from multiple users -----\n";
    foreach ($user_ids as $user_id) {
        removeCrossBrandCheckBlock($sc_id, $user_id);
    }
    echo "Processed all users -----\n";
}


/**
 * Reopen a closed account, typically closed by a bug or script mistakenly.
 * BE AWARE OF THIS - GET APPROVAL !!!!!!!!!!!!!!!!!!
 *
 * @param string $sc_id   Story Id used in logging and auditing
 * @param        $user_id The user id to re-open
 */
function reopenClosedAccount($sc_id, $user_id)
{
    $user = cu($user_id);
    if (empty($user)) {
        echo "ERROR: user with id {$user_id} not found in database!!\n";
        return;
    }

    removeSetting($sc_id, $user_id, 'closed_account');

    Phive('SQL')->sh($user_id)->query("update users set email    = substr(email,   8 + POSITION('_' IN substr(email,    8, 100)),100) where id = '{$user_id}' and email    like 'closed_%'");
    Phive('SQL')->sh($user_id)->query("update users set mobile   = substr(mobile,  8 + POSITION('_' IN substr(mobile,   8, 100)),100) where id = '{$user_id}' and mobile   like 'closed_%'");
    Phive('SQL')->sh($user_id)->query("update users set username = substr(username,8 + POSITION('_' IN substr(username, 8, 100)),100) where id = '{$user_id}' and username like 'closed_%'");
    Phive('SQL')->sh($user_id)->query("update users set nid      = substr(nid, 8, 100)  where id = '{$user_id}' and nid  like 'closed_%'");

    phive('UserHandler')->logAction($user, "Removed 'Closed_' from username,email,mobile,nid to reopen account - {$sc_id}", "comment");

    $user = cu($user_id);
    echo "User {$user->getId()} details trimmed 'closed_{$user_id}_' email[{$user->getAttribute('email')}] mobile[{$user->getAttribute('mobile')}] username[{$user->getAttribute('username')}] nid[{$user->getAttribute('nid')}].\n";
}

/**
 * Reopen a list of closed accounts, typically closed by a bug or script mistakenly.
 * BE AWARE OF THIS - GET APPROVAL !!!!!!!!!!!!!!!!!!
 *
 * @param string $sc_id    Story Id used in logging and auditing
 * @param        $user_ids The array of user ids to re-open
 */
function reopenClosedAccountMulti($sc_id, $user_ids)
{
    echo "Reopen closed multiple users -----\n";
    foreach ($user_ids as $user_id) {
        reopenClosedAccount($sc_id, $user_id);
    }
    echo "Processed all users -----\n";
}
