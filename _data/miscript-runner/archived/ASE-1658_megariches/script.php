<?php

require_once "{$root_folder_path}/phive/phive.php";
require_once "{$commons_folder}/common_functions.php";

$requester = "@mihailo.ilic";            # change requester
# $sc_id = 123456;           # enable to override story ID if different from folder name
$post_shortcut = true;       # enable posting of script output - set false if script produces secret output
$close_story = true;         # enable closing story - set false if same story requires multiple scripts to be run
$move_story_folder = true;  # enable moving story folder to archived folder - set false if not needed
$push_script_output = true;  # enable pushing story output to git - set false if not needed
$is_test = false;             # 'true' will override and disable the 4 variables above - set 'false' for production
$create_lockfile = true;     # handles creation and pushing of lockfile if set to true
# $extra_args can store additional parameter supplied from the pipeline - add after story_folder

$sql = Phive("SQL");
if (!isCli()) {
    die("Error: the script must be run in a CLI environment" . PHP_EOL);
}
$config_value = 'expire_time::{{phive||modDate||+1 day}}\nnum_days::60\ncost::0\nreward::0\nbonus_name::#deposit-newbonusoffers-mail-3\ndeposit_limit::40000\nrake_percent::3500\nbonus_code::\ndeposit_multiplier::1\nbonus_type::casinowager\nexclusive::1\nbonus_tag::\ntype::casino\ngame_tags::casino-playtech,slots,videoslots,scratch-cards,other,roulette,blackjack,videopoker,live,videoslots-nobonus,table\ncash_percentage::0\nmax_payout::0\nreload_code::ftd3{{date|Ymd}}\nexcluded_countries::\ndeposit_amount::0\ndeposit_max_bet_percent::0\nbonus_max_bet_percent::0\nmax_bet_amount::2001\nincluded_countries::AX AW BY BR CA CL HR DK EE FO FI PF GG IS IN IE IM JP JE LU MK MT MU MX NZ NO PY PE RS SI TW GB\nfail_limit::0\ngame_percents::rtp,rtp,rtp,rtp,0.1,0.1,0.1,0.1,0.1,0.1,0.1\nloyalty_percent::1\nallow_race::1\ntop_up::0\nstagger_percent::0.1\nkeep_winnings::1\ndeposit_threshold::2000\naward_id::31955\ncountry_version::SE:deposit-newbonusoffers-mail-3-SE,DK:deposit-newbonusoffers-mail-3-DK\nbrand_id::103;';

$sql->query("UPDATE config
SET config_value = '{$config_value}'
WHERE id = 902");

$sql->shs()->query("UPDATE config
SET config_value = '{$config_value}'
WHERE id = 902");
