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
run_command "docker-compose exec web drush state:set system.maintenance_mode 1 --input-format=integer -y"

# Git: Checkout correct branch.
run_command "git checkout $BRANCH"

# Git: Pull latest changes.
run_command "git pull"

# Installation: Install composer dependencies.
run_command "docker-compose exec web composer install"

# Configuration: Apply any database updates via Drush.
run_command "docker-compose exec web drush updatedb -y"

# Cache: Rebuild Drupal cache.
run_command "docker-compose exec web drush cache:rebuild"

# Configuration: Import site configuration via Drush.
run_command "docker-compose exec web drush config:import -y"

# Cache: Rebuild Drupal cache.
run_command "docker-compose exec web drush cache:rebuild"

# @todo Uncomment if we want entity schema updates in the database using this module: https://www.drupal.org/project/devel_entity_updates
# Configuration: Apply database entity schema updates via Drush
#run_command "docker-compose exec web drush entity:updates -y"

# Cache: Rebuild Drupal cache.
#run_command "docker-compose exec web drush cache:rebuild"

# Configuration: Disable maintenance mode
run_command "docker-compose exec web drush state:set system.maintenance_mode 0 --input-format=integer -y"
