#!/usr/bin/env bash
set -e
_folder_name_main="${1}"
_deploy_param_1="${2}"

#settle ticket details
_storage_folder="storage"
_settlement_file="F1_6068_Insert_Altenar_Refund.php"

exec_statement="EXECUTING COMMAND: "

sudo mkdir -p "${_folder_name_main}/${_settlement_folder}" || (echo "FAILED CREATING FOLDER" && exit 1)

#copy settle settlement file
sudo cp -v "${_deploy_param_1}/${_settlement_file}" "${_folder_name_main}/${_storage_folder}" || (echo "FAILED COPYING FILE" && exit 1)

cd "${_folder_name_main}" || (echo "FOLDER NOT FOUND" && exit 1)

settle_as_refund_command="php ${_folder_name_main}/${_storage_folder}/${_settlement_file}"
printf "Settling altenar ticket 136884 as void: \n"
printf "${exec_statement} sudo ${settle_as_refund_command}\n"
sudo ${settle_as_refund_command}
printf "Done \n"
