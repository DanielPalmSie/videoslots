<?php

/**
 * Cross-link as local-user-id to a remote-user-id on a remote-brand.
 *
 * @param string  $sc_id    Story Id used in logging and auditing
 * @param integer $local_user_id The user_id that setting should be set to link to remote user id.
 * @param string  $remote_brand The remote brand that the local user should be linked to (VS or MRV).
 * @param integer $remote_user_id The remote user_id that the local user should be linked to.
 */
function crossLinkUserToOtherBrand($sc_id, $local_user_id, $remote_brand, $remote_user_id)
{
    $remote_brand = strtoupper($remote_brand);
    if ($remote_brand == "VS") {
        $setting = 'c100_id';
    } else if ($remote_brand == "MRV") {
        $setting = 'c101_id';
    } else {
        echo "ERROR: wrong brand {$remote_brand} passed - should be VS or MRV!!\n";
        return;
    }

    $user = cu($local_user_id);
    if (empty($user)) {
        echo "ERROR: user with id {$local_user_id} not found in database!!\n";
        return;
    }

    echo "Linking User ID {$local_user_id} with {$remote_brand} User ID {$remote_user_id} \n";
    $user->setSetting($setting, $remote_user_id, false);
    phive('UserHandler')->logAction($user, "Manually added {$setting} (crossbrand link) setting for user - {$sc_id}", "comment");
    $user = cu($local_user_id);  // refetch from db.
    echo "Updated User ID {$local_user_id} setting {$setting}: {$user->getSetting("{$setting}")}\n";
}

/**
 * Remove Cross-link from local-user-id to a remote-brand.
 *
 * @param string  $sc_id    Story Id used in logging and auditing
 * @param integer $local_user_id The user_id that setting should be removed to unlink.
 * @param string  $remote_brand The remote brand that the local user should be unlinked from (VS or MRV).
 */
function removeCrossLinkUserToOtherBrand($sc_id, $local_user_id, $remote_brand)
{
    $remote_brand = strtoupper($remote_brand);
    if ($remote_brand == "VS") {
        $setting = 'c100_id';
    } else if ($remote_brand == "MRV") {
        $setting = 'c101_id';
    } else {
        echo "ERROR: wrong brand {$remote_brand} passed - should be VS or MRV!!\n";
        return;
    }

    $user = cu($local_user_id);
    if (empty($user)) {
        echo "ERROR: user with id {$local_user_id} not found in database!!\n";
        return;
    }

    echo "UnLinking {$remote_brand} from User ID {$local_user_id} \n";

    if ($user->hasSetting($setting)) {
        $user->deleteSetting($setting);
        phive('UserHandler')->logAction($user, "Manually cross-brand UNlinking by removing {$setting} setting - {$sc_id}", "comment");
        $user = cu($local_user_id);  // refetch from db.
        echo "User {$user->getId()}: Removed cross-brand link setting {$setting}: [{$user->getSetting($setting)}].\n";
    } else {
        echo "User {$user->getId()}: does not have cross-brand link setting {$setting}: [{$user->getSetting($setting)}].\n";
    }
}
