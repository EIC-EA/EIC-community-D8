#!/bin/sh
# Runs all commands necessary to perform a clean install & migrate of the site.
#
# Arguments:
#   BRANCH The Git branch to deploy.

# Immediately exit a script when it encounters an error.
set -e

# Print usage info.
#
# Arguments:
#   COLOR The foreground color of the text.
PRINT_USAGE_INFO() {
  COLOR=${1:-$COLOR_DEFAULT}
  printf "\n%b" "$COLOR"
  cat <<EOF
Usage: sh install-migrate-clean.sh BRANCH

Arguments:
  BRANCH    The Git branch to deploy.
EOF
}

# Constants
COLOR_DEFAULT="\e[39m"
COLOR_WHITE="\e[97m"
COLOR_GREEN="\e[92m"
COLOR_RED="\e[91m"
BG_COLOR_DEFAULT="\e[49m"
BG_COLOR_GREEN="\e[42m"
DRUSH_CMD="../vendor/bin/drush"

# Arguments
BRANCH=$1

# Exit script if required arguments are missing.
if [ -z "$BRANCH" ]; then
  printf "\n%bMissing required arguments.\n" "$COLOR_RED"
  PRINT_USAGE_INFO
  exit 1
fi

# Runs a given command and exits the script if it fails.
#
# Arguments:
#   COMMAND The command to run.
#   ERROR_MESSAGE The error message to display if the command fails.
#   ERROR_CODE The error code to return if the command fails.
run_command() {
  COMMAND=$1
  DEFAULT_ERROR_MESSAGE="Command failed... $COMMAND"
  DEFAULT_ERROR_CODE=1
  ERROR_MESSAGE=${2:-$DEFAULT_ERROR_MESSAGE}
  ERROR_CODE=${3:-$DEFAULT_ERROR_CODE}

  printf "\n%b%bRunning command...%b %b%s\n\n%b" "$COLOR_WHITE" "$BG_COLOR_GREEN" "$BG_COLOR_DEFAULT" "$COLOR_GREEN" "$COMMAND" "$COLOR_DEFAULT"

  if ! $COMMAND; then
    printf "\n%b%s\n" "$COLOR_RED" "$ERROR_MESSAGE"
    exit "$ERROR_CODE"
  fi
}

# Configuration: Enable maintenance mode
cd web
run_command "$DRUSH_CMD state:set system.maintenance_mode 1 --input-format=integer -y"
cd ..

# Git: Checkout correct branch.
run_command "git checkout $BRANCH"

# Git: Pull latest changes.
run_command "git pull"

# Installation: Install composer dependencies.
run_command "composer install"
# Move into the Drupal webroot.
cd web

# Configuration: Apply any database updates via Drush.
run_command "$DRUSH_CMD updatedb -y"

# Cache: Rebuild Drupal cache.
run_command "$DRUSH_CMD cache:rebuild"

# Configuration: Import site configuration via drush.
run_command "$DRUSH_CMD config:import -y"

# Cache: Rebuild Drupal cache.
run_command "$DRUSH_CMD cache:rebuild"

# Configuration: Disable maintenance mode
run_command "$DRUSH_CMD state:set system.maintenance_mode 0 --input-format=integer -y"

# Cache: Rebuild Drupal cache.
run_command "$DRUSH_CMD cache:rebuild"
