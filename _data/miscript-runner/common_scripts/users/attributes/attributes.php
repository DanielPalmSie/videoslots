<?php

/**
 * Add an attribute from a user to a particular value.
 *
 * @param string  $sc_id   Story Id used in logging and auditing
 * @param integer $user_id The user id(or object) to add attribute
 * @param string  $setting The name of the setting to add
 * @param string  $value   The value of the setting
 */
function addAttribute($sc_id, $user_id, $attribute, $value)
{
    $user = cu($user_id);
    if (empty($user)) {
        echo "ERROR: user with id {$user_id} not found in database!!\n";
        return;
    }

    if ($user->getAttribute($attribute) == $value) {
        echo "User {$user->getId()}: already has {$attribute} attribute: [{$user->getAttribute($attribute)}].\n";
    } else {
        $user->setAttribute($attribute, $value);
        phive('UserHandler')->logAction($user, "Added manually {$attribute} attribute - {$sc_id}", "comment");
        echo "User {$user->getId()}: Added {$attribute}: [{$user->getAttribute($attribute, true)}].\n";
    }
}
