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

# RUD: 2025-03-22
laravel_command="console ics:rectify 2025-03-22 2025-03-22 RUD"
printf "EXECUTING COMMAND: ${laravel_command}\n"
sudo php ${laravel_command}

# RUD: 2025-03-26
laravel_command="console ics:rectify 2025-03-26 2025-03-26 RUD"
printf "EXECUTING COMMAND: ${laravel_command}\n"
sudo php ${laravel_command}

# RUD: 2025-03-28
laravel_command="console ics:rectify 2025-03-28 2025-03-28 RUD"
printf "EXECUTING COMMAND: ${laravel_command}\n"
sudo php ${laravel_command}

# RUD: 2025-03-29
laravel_command="console ics:rectify 2025-03-29 2025-03-29 RUD"
printf "EXECUTING COMMAND: ${laravel_command}\n"
sudo php ${laravel_command}

# RUD: 2025-03-31
laravel_command="console ics:rectify 2025-03-31 2025-03-31 RUD"
printf "EXECUTING COMMAND: ${laravel_command}\n"
sudo php ${laravel_command}

# RUD: 2025-04-01
laravel_command="console ics:rectify 2025-04-01 2025-04-01 RUD"
printf "EXECUTING COMMAND: ${laravel_command}\n"
sudo php ${laravel_command}

# RUD: 2025-04-02
laravel_command="console ics:rectify 2025-04-02 2025-04-02 RUD"
printf "EXECUTING COMMAND: ${laravel_command}\n"
sudo php ${laravel_command}

# RUD: 2025-04-03
laravel_command="console ics:rectify 2025-04-03 2025-04-03 RUD"
printf "EXECUTING COMMAND: ${laravel_command}\n"
sudo php ${laravel_command}

# RUD: 2025-04-04
laravel_command="console ics:rectify 2025-04-04 2025-04-04 RUD"
printf "EXECUTING COMMAND: ${laravel_command}\n"
sudo php ${laravel_command}

# RUD: 2025-04-05
laravel_command="console ics:rectify 2025-04-05 2025-04-05 RUD"
printf "EXECUTING COMMAND: ${laravel_command}\n"
sudo php ${laravel_command}

# RUD: 2025-04-07
laravel_command="console ics:rectify 2025-04-07 2025-04-07 RUD"
printf "EXECUTING COMMAND: ${laravel_command}\n"
sudo php ${laravel_command}

# RUD: 2025-04-08
laravel_command="console ics:rectify 2025-04-08 2025-04-08 RUD"
printf "EXECUTING COMMAND: ${laravel_command}\n"
sudo php ${laravel_command}

# RUD: 2025-04-09
laravel_command="console ics:rectify 2025-04-09 2025-04-09 RUD"
printf "EXECUTING COMMAND: ${laravel_command}\n"
sudo php ${laravel_command}

# RUD: 2025-04-10
laravel_command="console ics:rectify 2025-04-10 2025-04-10 RUD"
printf "EXECUTING COMMAND: ${laravel_command}\n"
sudo php ${laravel_command}

# RUD: 2025-04-12
laravel_command="console ics:rectify 2025-04-12 2025-04-12 RUD"
printf "EXECUTING COMMAND: ${laravel_command}\n"
sudo php ${laravel_command}

# RUD: 2025-04-14
laravel_command="console ics:rectify 2025-04-14 2025-04-14 RUD"
printf "EXECUTING COMMAND: ${laravel_command}\n"
sudo php ${laravel_command}

# RUD: 2025-04-15
laravel_command="console ics:rectify 2025-04-15 2025-04-15 RUD"
printf "EXECUTING COMMAND: ${laravel_command}\n"
sudo php ${laravel_command}

# RUD: 2025-04-16
laravel_command="console ics:rectify 2025-04-16 2025-04-16 RUD"
printf "EXECUTING COMMAND: ${laravel_command}\n"
sudo php ${laravel_command}

# RUD: 2025-04-20
laravel_command="console ics:rectify 2025-04-20 2025-04-20 RUD"
printf "EXECUTING COMMAND: ${laravel_command}\n"
sudo php ${laravel_command}

# RUD: 2025-04-21
laravel_command="console ics:rectify 2025-04-21 2025-04-21 RUD"
printf "EXECUTING COMMAND: ${laravel_command}\n"
sudo php ${laravel_command}
