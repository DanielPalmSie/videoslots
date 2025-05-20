<?php
require_once "{$commons_folder}/common_functions.php";
require_once "{$commons_folder}/users/cross_brand/cross_brand.php";

$requester = "@";      // change requester
// $sc_id = 123;       // enable to override story Id if different from folder name
$post_shortcut = true; // enable posting of script output to shortcut, set false if script produces secret output


/**
 * Cross-link as local-user-id to a remote-user-id on a remote-brand.
 *
 * @param string  $sc_id    Story Id used in logging and auditing
 * @param integer $local_user_id The user_id that setting should be set to link to remote user id.
 * @param string  $remote_brand The remote brand that the local user should be linked to (VS or MRV).
 * @param integer $remote_user_id The remote user_id that the local user should be linked to.
 */
crossLinkUserToOtherBrand(112233, 1010109, "MRV", 20207);
