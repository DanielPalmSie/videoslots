#!/usr/bin/env bash
set -e
_folder_name_main="${1}"
_deploy_param_1="${2}"

# this file should be executable (chmod +x bash_script.sh)
_script_folder="storage"
_script_file="bets_rounds_fixtures.php" #TODO: Replace with actual file name (PHP)
_rounds_fixtures_file="roundsFixtures.json"

exec_statement="EXECUTING COMMAND: "

sudo mkdir -p "${_folder_name_main}/${_script_folder}" || (echo "FAILED CREATING FOLDER" && exit 1)

# copy php script file
sudo cp -v "${_deploy_param_1}/${_script_file}" "${_folder_name_main}/${_script_folder}" || (echo "FAILED COPYING FILE" && exit 1)

# copy rounds fixtures file
sudo cp -v "${_deploy_param_1}/${_rounds_fixtures_file}" "${_folder_name_main}/${_script_folder}" || (echo "FAILED COPYING FILE" && exit 1)

cd "${_folder_name_main}" || (echo "FOLDER NOT FOUND" && exit 1)

# run php script file
command="${_script_folder}/${_script_file}"
printf "EXECUTING COMMAND: php ${command}\n"
sudo php ${command}
