#!/usr/bin/env bash
set -e

_folder_name_main="${1}"

cd "${_folder_name_main}" || (echo "FOLDER NOT FOUND" && exit 1)

command="console seeder:up 20250422131235"

printf "EXECUTING COMMAND: %s\n" "${command}"

sudo php ${command}
