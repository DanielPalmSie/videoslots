#!/usr/bin/env bash
set -e
# Production deploy script
#  Script parameters:
#    none: update folder and run dependency handling
#  Exits with status 1 on error.

function handle_dependencies {
    local _folder_name="${1}"
    cd "${_folder_name}"
    if command -v composer &>/dev/null; then
        if [[ -f "composer.json" ]]; then
            echo -e "${cBlue}SETTING PROPER FOLDER PERMISSIONS${c0}"
            sudo bash ./git-scripts/set_permissions.sh || {
                echo -e "${fail_msg}" && exit 1
            }
            echo -e "${success_msg}"
            echo -e "${cBlue}COMPOSER INSTALL AND AUTOLOAD RUNNING${c0}"
            sudo composer install -a -n --no-dev || {
                echo -e "${fail_msg}" && exit 1
            }
            echo -e "${success_msg}"
        fi
    fi
}

function deploy_main {
    local _folder_name="${1}"
    cd "${_folder_name}"
    echo -e "${cDGreen}[ STARTING DEPLOY ON ${HOSTNAME}: ${_folder_name} ]${c0}"
    echo -e "${cBlue}REVISION PRE-DEPLOY: ${_folder_name}${c0}"
    git show -q
    echo -e "${cBlue}FETCHING/PULLING LATEST${c0}"
    for ((attempt = 1; attempt <= MAX_ATTEMPTS; attempt++)); do
        sudo git pull && break
    done
    if ((attempt > MAX_ATTEMPTS)); then
        echo -e "${fail_msg}" && exit 1
    fi
    echo -e "${success_msg}"
    echo -e "${cBlue}FOLDER STATUS: ${_folder_name}${c0}"
    git status
    echo -e "${cBlue}REVISION POST-DEPLOY: ${_folder_name}${c0}"
    git show -q
}

__ESC__="\033"
c0="${__ESC__}[0m"
cDRed="${__ESC__}[1;31m"
cDGreen="${__ESC__}[1;32m"
cBlue="${__ESC__}[1;34m"
folder_name_main="$(cd -P -- "$(dirname -- "${BASH_SOURCE[0]}")" && cd ../ &>/dev/null && pwd -P)"
success_msg="${cBlue}SUCCESS${c0}"
fail_msg="${cDRed}DEPLOY FAILED ON: ${HOSTNAME}${c0}"
MAX_ATTEMPTS=5

deploy_main "${folder_name_main}"
handle_dependencies "${folder_name_main}"
echo -e "${cDGreen}[ FINISHED DEPLOY ON ${HOSTNAME} ]${c0}"
