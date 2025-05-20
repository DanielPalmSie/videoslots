#!/usr/bin/env bash
set -e

root_folder_path="${1}"
story_folder="${2}"
extra_args="${3}"

# this file should be executable (chmod +x bash_script.sh)
# target_folder="${root_folder_path}/storage/files/manual_settlement/"
# target_file="settleable_tickets.csv"
# extra_args can store additional parameter supplied from the pipeline

# sudo mkdir -p "${target_folder}" || (echo "Failed creating folder: ${target_folder}" && exit 1)
# sudo cp -av "${story_folder}/${target_file}" "${target_folder}" || (echo "Failed copying file: ${target_file}" && exit 1)

cd "${root_folder_path}" || {
    echo "Error: ${root_folder_path} not found" && exit 1
}

# cat "${target_folder}/${target_file}"

# use prefix 'console' for 'admin2' only, 'artisan' for 'user-service' and other Laravel projects
# change 'command' below, as required:

##DEVS-14984
command="artisan profile:unlink 1967201574 videoslots"
echo "Executing command: ${command}"
sudo php ${command}

##DEVS-15177s
command="artisan profile:re-link 4000002699 megariches 1967054687 videoslots"
echo "Executing command: ${command}"
sudo php ${command}
