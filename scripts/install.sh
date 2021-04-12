#!/bin/sh

# Immediately exit a script when it encounters an error.
set -e

print_usage_info() {
  cat <<EOF

Install/update the site and migrate the content.

Usage: sh scripts/install.sh [-h|--help] [--root=CMD_ROOT] [--drush=DRUSH] [--git=BRANCH] [--no-build] [--clean] [--migrate] [--admin-password=PASSWORD]

Options:
  -h | --help                 Print command usage info.
  --root=CMD_ROOT             Location to run the commands from. E.g. "docker-compose exec web"
  --drush=DRUSH               Location of the of the drush command. E.g. "docker-compose exec web drush"
  --git=BRANCH                Perform git checkout. Git branch to checkout.
  --no-build                  SKIP build.
  --clean                     Perform clean install of the site.
  --migrate                   Perform migration.
  --admin-password=PASSWORD   Password to set for the admin user. (first user)
EOF
}

# Constants
COLOR_DEFAULT="\e[39m"
COLOR_WHITE="\e[97m"
COLOR_GREEN="\e[92m"
COLOR_YELLOW="\e[93m"
COLOR_RED="\e[91m"
BG_COLOR_DEFAULT="\e[49m"
BG_COLOR_GREEN="\e[42m"
THEME_PATH="web/themes/custom/eic_community"
DEFAULT_CONTENT_MODULES="eic_default_content default_content hal serialization"

# Defaults
PERFORM_GIT_CHECKOUT=false
PERFORM_CLEAN_INSTALL=false
PERFORM_MIGRATION=false
PERFORM_ADMIN_PASSWORD_CHANGE=false
SKIP_BUILD=false
GIT_BRANCH="develop"
CMD_ROOT=""
DRUSH_CMD="vendor/bin/drush --root=$PWD/web"
ADMIN_PASSWORD=""

# Get input parameters
while [ "$1" != "" ]; do
  PARAM=$(echo $1 | awk -F= '{print $1}')
  VALUE=$(echo $1 | awk -F= '{print $2}')
  case $PARAM in
  -h | --help)
    print_usage_info
    exit
    ;;
  --root)
    CMD_ROOT=$VALUE
    echo "Changed command root location to '$CMD_ROOT'"
    ;;
  --drush)
    DRUSH_CMD=${VALUE:-$DRUSH_CMD}
    echo "Changed drush command location to '$DRUSH_CMD'"
    ;;
  --git)
    PERFORM_GIT_CHECKOUT=true
    GIT_BRANCH=${VALUE:-$GIT_BRANCH}
    echo "Will perform git checkout. Branch: '$GIT_BRANCH'"
    ;;
  --clean)
    PERFORM_CLEAN_INSTALL=true
    echo "Will perform clean install."
    ;;
  --migrate)
    PERFORM_MIGRATION=true
    echo "Will perform migration."
    ;;
  --admin-password)
    if [ -n "$VALUE" ]; then
      PERFORM_ADMIN_PASSWORD_CHANGE=true
      ADMIN_PASSWORD=$VALUE
      echo "Will perform admin password change."
    else
      printf "\n%bNew admin password missing. Skipping password change.%b\n" "$COLOR_YELLOW" "$COLOR_DEFAULT"
    fi
    ;;
  --no-build)
    SKIP_BUILD=true
    echo "Skipping build."
    ;;
  *)
    printf "\n%bERROR: Unknown parameter \"$PARAM\"%b\n" "$COLOR_RED" "$COLOR_DEFAULT"
    print_usage_info
    exit 1
    ;;
  esac
  shift
done

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

if [ "$PERFORM_CLEAN_INSTALL" = false ]; then
  # Configuration: Enable maintenance mode (only when not performing a clean install).
  run_command "$DRUSH_CMD state:set system.maintenance_mode 1 --input-format=integer -y"
fi

if [ "$PERFORM_GIT_CHECKOUT" = true ]; then
  # Git: Checkout correct branch.
  run_command "$CMD_ROOT git checkout $GIT_BRANCH"

  # Git: Pull latest changes.
  run_command "$CMD_ROOT git pull"
fi

if [ "$SKIP_BUILD" = false ]; then
  # Installation: Install composer dependencies.
  run_command "$CMD_ROOT composer install"

  # Install new npm packages on eic_community theme.
  run_command "$CMD_ROOT npm install --prefix $THEME_PATH"

  # Rebuild eic_community theme assets.
  run_command "$CMD_ROOT npm run build --prefix $THEME_PATH"
fi

if [ "$PERFORM_CLEAN_INSTALL" = true ]; then
  # Installation: Install clean website via Toolkit.
  run_command "$CMD_ROOT ./vendor/bin/run toolkit:install-clean --config-file runner.yml.dist"

  # Install EIC default content module.
  run_command "$DRUSH_CMD en $DEFAULT_CONTENT_MODULES -y"

  # Uninstall EIC default content module and dependencies.
  run_command "$DRUSH_CMD pmu $DEFAULT_CONTENT_MODULES -y"
fi

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

if [ "$PERFORM_MIGRATION" = true ]; then
  # Migration: Migrate all content from EIC Drupal 7.
  run_command "$DRUSH_CMD migrate:import --group migrate_drupal_7 --continue-on-failure"

  # Migration: Migrate all content from Challenge Platform Drupal 7.
  run_command "$DRUSH_CMD migrate:import --group migrate_drupal_7_challenge_platform --continue-on-failure"
fi

if [ "$PERFORM_ADMIN_PASSWORD_CHANGE" = true ]; then
  # Configuration: Update password of admin user (first user).
  run_command "$DRUSH_CMD user:password MasterChef1 $ADMIN_PASSWORD" "Setting admin user password failed."
fi

# Configuration: Disable maintenance mode
run_command "$DRUSH_CMD state:set system.maintenance_mode 0 --input-format=integer -y"

# Cache: Rebuild Drupal cache.
run_command "$DRUSH_CMD cache:rebuild"

# Cache: Rebuild Drupal cache.
run_command "$DRUSH_CMD cache:rebuild"
