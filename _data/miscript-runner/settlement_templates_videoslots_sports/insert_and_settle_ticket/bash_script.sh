#!/usr/bin/env bash
set -e
_folder_name_main="${1}"
_deploy_param_1="${2}"

# this file should be executable (chmod +x bash_script.sh)
# insert ticket details
_insert_ticket_folder="storage"
_insert_ticket_script="insert_ticket_ch_sample.php" #TODO: Replace with actual insert ticket file name (PHP)

#settle ticket details
_settlement_folder="storage/files/manual-settlement/"
_settlement_file="settleable_tickets_ch_sample.csv" #TODO: Replace with actual settlement ticket file name (CSV)

sudo mkdir -p "${_folder_name_main}/${_settlement_folder}" || (echo "FAILED CREATING FOLDER" && exit 1)

#copy insert script
sudo cp -v "${_deploy_param_1}/${_insert_ticket_script}" "${_folder_name_main}/${_insert_ticket_folder}" || (echo "FAILED COPYING FILE" && exit 1)

#copy settle settlement file
sudo cp -v "${_deploy_param_1}/${_settlement_file}" "${_folder_name_main}/${_settlement_folder}" || (echo "FAILED COPYING FILE" && exit 1)

cd "${_folder_name_main}" || (echo "FOLDER NOT FOUND" && exit 1)

#insert ticket
insert_command="${_insert_ticket_folder}/${_insert_ticket_script}"
printf "EXECUTING COMMAND: ${insert_command}\n"
sudo php ${insert_command}

#settle ticket
settle_command="artisan sts:process_manual_settlement_no_balance_change ${_settlement_file} --force"
printf "EXECUTING COMMAND: ${settle_command}\n"
sudo php ${settle_command}
