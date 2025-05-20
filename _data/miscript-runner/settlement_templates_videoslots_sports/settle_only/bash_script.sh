#!/usr/bin/env bash
set -e
_folder_name_main="${1}"
_deploy_param_1="${2}"

# this file should be executable (chmod +x bash_script.sh)

#settle ticket details
_settlement_folder="storage/files/manual-settlement/"
_settlement_file="settleable_tickets_ch_sample_settle_only.csv" #TODO: Replace with actual settlement ticket file name (CSV)

sudo mkdir -p "${_folder_name_main}/${_settlement_folder}" || (echo "FAILED CREATING FOLDER" && exit 1)

#copy settle settlement file
sudo cp -v "${_deploy_param_1}/${_settlement_file}" "${_folder_name_main}/${_settlement_folder}" || (echo "FAILED COPYING FILE" && exit 1)

cd "${_folder_name_main}" || (echo "FOLDER NOT FOUND" && exit 1)

#settle ticket
settle_command="artisan sts:process_manual_settlement_no_balance_change ${_settlement_file} --force"
printf "EXECUTING COMMAND: ${settle_command}\n"
sudo php ${settle_command}
