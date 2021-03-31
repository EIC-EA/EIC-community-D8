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
Usage: sh scripts/deploy.sh [BRANCH|-g] [-b] [-i] [-m PASSWORD]

Options:
  -g          SKIP Git checkout. Either specify this, OR a branch to check out.
  -b          SKIP build.
  -i          Perform a clean install of the site.
  -m PASSWORD Perform migration. Specify a password to set the uid 1 password to.
Arguments:
  BRANCH      The Git branch to deploy.
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
THEME_PATH="web/themes/custom/eic_community"
DEFAULT_CONTENT_MODULES="eic_default_content default_content hal serialization"

# Define list of arguments expected in the input
optstring=":gbim:"

while getopts ${optstring} arg; do
  case ${arg} in
    g)
      SKIP_GIT='true'
      echo "Skipping Git checkout/pull."
      ;;
    b)
      SKIP_BUILD='true'
      echo "Skipping build."
      ;;
    i)
      PERFORM_INSTALL='true'
      echo "Will perform install."
      ;;
    m)
      PERFORM_MIGRATION='true'
      echo "Will perform migration."
      ;;
  esac
done

# Arguments
if [ -z "$SKIP_GIT" ]; then
  BRANCH=$1

  # Exit script if required arguments are missing.
  if [ -z "$BRANCH" ]; then
    printf "\n%bMissing required arguments.\n" "$COLOR_RED"
    PRINT_USAGE_INFO
    exit 1
  fi
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

if [ -z "$PERFORM_INSTALL" ]; then
  # Configuration: Enable maintenance mode (only when not performing an
  # install).
  cd web
  run_command "$DRUSH_CMD state:set system.maintenance_mode 1 --input-format=integer -y"
  cd ..
fi

if [ -z "$SKIP_GIT" ]; then
  # Git: Checkout correct branch.
  run_command "git checkout $BRANCH"

  # Git: Pull latest changes.
  run_command "git pull"
fi

if [ -z "$SKIP_BUILD" ]; then
  # Installation: Install composer dependencies.
  run_command "composer install"

  # Install new npm packages on eic_community theme.
  run_command "npm install --prefix $THEME_PATH"

  # Rebuild eic_community theme assets.
  run_command "npm run build --prefix $THEME_PATH"
fi

if [ -n "$PERFORM_INSTALL" ]; then
  # Installation: Install clean website via Toolkit.
  run_command "./vendor/bin/run toolkit:install-clean --config-file runner.yml.dist"

  # Move into the Drupal webroot in order to execute drush commands.
  cd web

  # Install EIC default content module.
  run_command "$DRUSH_CMD en $DEFAULT_CONTENT_MODULES -y"

  # Uninstall EIC default content module and dependencies.
  run_command "$DRUSH_CMD pmu $DEFAULT_CONTENT_MODULES -y"

  # Move back to the project's root folder.
  cd ..
fi

# Move into the Drupal webroot.
cd web

# Make sure the eic_deploy module is enabled *before* we run updates.
# This may be removed again when the module has been successfully enabled on
# all installed environments.
run_command "$DRUSH_CMD en eic_deploy -y"

# Configuration: Apply any database updates via Drush.
run_command "$DRUSH_CMD updatedb -y"

# Cache: Rebuild Drupal cache.
run_command "$DRUSH_CMD cache:rebuild"

# Configuration: Import site configuration via drush.
run_command "$DRUSH_CMD config:import -y"

# Cache: Rebuild Drupal cache.
run_command "$DRUSH_CMD cache:rebuild"

if [ -n "$PERFORM_MIGRATION" ]; then
  # Migration: Migrate all content from EIC Drupal 7.
  run_command "$DRUSH_CMD migrate:import --group migrate_drupal_7 --continue-on-failure"

  # Migration: Migrate all content from Challenge Platform Drupal 7.
  run_command "$DRUSH_CMD migrate:import --group migrate_drupal_7_challenge_platform --continue-on-failure"

  # Configuration: Update password of admin user (first user).
  run_command "$DRUSH_CMD user:password MasterChef1 $PASSWORD" "Setting admin user password failed."
fi

# Configuration: Disable maintenance mode
run_command "$DRUSH_CMD state:set system.maintenance_mode 0 --input-format=integer -y"

# Cache: Rebuild Drupal cache.
run_command "$DRUSH_CMD cache:rebuild"

# Cache: Rebuild Drupal cache.
run_command "$DRUSH_CMD cache:rebuild"
