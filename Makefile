include .env

#silent by default
ifndef VERBOSE
.SILENT:
endif

ifeq (run,$(firstword $(MAKECMDGOALS)))
  RUN_ARGS := $(wordlist 2,$(words $(MAKECMDGOALS)),$(MAKECMDGOALS))
  $(eval $(RUN_ARGS):;@:)
endif

UNAME := $(shell uname)

setup:
  ifeq ($(UNAME),Darwin)
		$(call do_macos_setup)
  endif
	$(call do_setup)

start:
	$(call do_start)

restart:
	$(call do_restart)

update:
	$(call do_update)

cc:
	$(call do_cc)

stop:
	$(call do_stop)

destroy:
	$(call do_destroy)

run:
	docker exec -it -w ${APP_ROOT}/lib/themes/eic_community ${APP_NAME}_php bash -c 'npm run $(RUN_ARGS)'

cex:
	docker exec -it ${APP_NAME}_php bash -c '/app/vendor/bin/drush cex -y'

import-db:
	docker exec -i ${APP_NAME}_mysql bash -c 'exec mysql -u${DATABASE_USERNAME} ${DATABASE_NAME}' < $(FILE)

ssh:
	docker exec -it --user web ${APP_NAME}_php bash

ssh_cypress:
	docker exec -it ${APP_NAME}_cypress bash

help:
	$(call do_display_commands)

info:
	$(call do_display_app_info)

build-front:
	$(call do_build_front)

reload-fixtures:
	$(call do_reload_fixtures)

clear-indexes:
	$(call clear_indexes)

define do_build_front
	echo -e 'Installing node modules and building...'
	docker exec -it -w ${APP_ROOT}/lib/themes/eic_community ${APP_NAME}_php bash -c "npm install \
		&& npm run build \
		&& npm run react-production"
	echo -e 'Generating storybook...'
	docker exec -it -w ${APP_ROOT}/lib/themes/eic_community ${APP_NAME}_php bash -c "npm run pregenerate-storybook \
  		&& npm run generate-storybook \
  		&& npm run postgenerate-storybook"
endef

define do_reload_fixtures
	echo -e 'Reloading fixtures...'
	docker exec -it ${APP_NAME}_php bash -c './vendor/bin/drush fixtures:reload all'
	echo -e '\n'
endef

define do_setup
	echo -e 'Setting up ${APP_NAME}...'
	docker-compose build --build-arg UID=$(shell id -u) --build-arg GID=$(shell id -g)
	docker-compose up -d
	docker exec -it ${APP_NAME}_php bash -c 'composer install --no-progress'

	$(call do_create_symlinks)
	$(call do_build_front)
	docker exec -it ${APP_NAME}_php bash -c 'cp -n ${APP_ROOT}/web/sites/default/default.settings.local.php ${APP_ROOT}/web/sites/default/settings.php'
	docker exec -it ${APP_NAME}_php bash -c './vendor/bin/drush site-install minimal --site-name=${APP_NAME} --account-name=${DRUPAL_ADMIN_USER} --account-pass=${DRUPAL_ADMIN_PASSWORD} --existing-config -y'
	docker exec -it ${APP_NAME}_php bash -c './vendor/bin/drush cr'
	$(call clear_indexes)
	$(call do_reload_fixtures)
	docker exec -it ${APP_NAME}_php bash -c './vendor/bin/drush cr'
	echo -e '\n'
	echo -e '\e[42m${APP_NAME} setup completed\e[0m'
	$(call do_display_app_info)
	$(call do_display_commands)
endef

define do_macos_setup
	echo -e 'Setting up ${APP_NAME}...'
	echo -e 'You have been identified as running on macOS'
	echo -e 'Please be sure you read \e[36m"Running on macOS"\e[0m within the README file'
	echo -e 'Copying macos volume configs as docker-compose.override.yml'
	$(shell cp -n ./docker/macos.volumes.yml docker-compose.override.yml)
endef

define do_start
	echo -e 'Starting ${APP_NAME}...'
	docker-compose up -d

	docker exec -it ${APP_NAME}_php bash -c './vendor/bin/drush cim -y'
	docker exec -it ${APP_NAME}_php bash -c './vendor/bin/drush cr'
	echo -e '\n'
	echo -e '\e[42m${APP_NAME} started\e[0m'
	$(call do_display_app_info)
	$(call do_display_commands)
endef

define do_restart
	echo -e 'Restarting ${APP_NAME}...'
	docker-compose down
	docker-compose up -d

	docker exec -it ${APP_NAME}_php bash -c  './vendor/bin/drush cr'
	echo -e '\n'
	echo -e '\e[42m${APP_NAME} restarted\e[0m'
	$(call do_display_app_info)
	$(call do_display_commands)
endef

define do_update
	echo -e 'Updating ${APP_NAME}...'
	docker exec -it ${APP_NAME}_php bash -c 'composer install --no-progress'
	$(call do_build_front)
	docker exec -it ${APP_NAME}_php bash -c './vendor/bin/drush cr'
	docker exec -it ${APP_NAME}_php bash -c './vendor/bin/drush cim -y'
	docker exec -it ${APP_NAME}_php bash -c './vendor/bin/drush updb -y'
	docker exec -it ${APP_NAME}_php bash -c './vendor/bin/drush cr'
	echo -e '\n'
	echo -e '\e[42m${APP_NAME} updated\e[0m'
endef

define do_cc
	echo -e 'Clearing ${APP_NAME} caches...'
	docker exec -it ${APP_NAME}_php bash -c './vendor/bin/drush cr'
endef

define do_create_symlinks
	echo -e 'Creating symlinks'
	docker exec -it ${APP_NAME}_php bash -c 'rm -rf /app/web/modules/custom'
	docker exec -it ${APP_NAME}_php bash -c 'ln -sf ../../lib/modules /app/web/modules/custom'
	docker exec -it ${APP_NAME}_php bash -c 'rm -rf /app/web/themes/custom'
	docker exec -it ${APP_NAME}_php bash -c 'ln -sf ../../lib/themes /app/web/themes/custom'
	docker exec -it ${APP_NAME}_php bash -c 'rm -rf /app/web/profiles/custom'
	docker exec -it ${APP_NAME}_php bash -c 'ln -sf ../../lib/profiles /app/web/profiles/custom'
	docker exec -it ${APP_NAME}_php bash -c 'rm -rf /app/web/community'
	docker exec -it ${APP_NAME}_php bash -c 'ln -sf /app/web /app/web/community'
endef

define do_stop
	echo -e 'Stopping ${APP_NAME}...'
	docker-compose down
	echo -e '\n'
	echo -e '\e[42m${APP_NAME} stopped\e[0m'
endef

define clear_indexes
	echo 'Clearing indexes'
	docker-compose exec php bash -c "curl -X POST -H 'Content-Type: application/json' 'http://solr:8983/solr/${SEARCH_INDEX}/update?commit=true' -d '{\"delete\":{\"query\":\"*:*\"}}'"
endef

define do_destroy
	echo -e 'Destroying ${APP_NAME}...'
	docker-compose down --volumes
	echo -e '\n'
	echo -e '\e[42m${APP_NAME} stopped and data deleted\e[0m'
endef

define do_display_app_info
	echo -e '\n'
	echo -e '\e[1m--- ${APP_NAME} APP INFO ---\e[0m'
	echo -e '\n'
	echo -e 'APP URL: \e[36mhttp://localhost:${APP_PORT}\e[0m\nAdmin user credentials: ${DRUPAL_ADMIN_USER} - ${DRUPAL_ADMIN_PASSWORD}\nDatabase port: ${DATABASE_PORT}'
endef

define do_display_commands
	echo -e '\n'
	echo -e '--- AVAILABLE COMMANDS ---'
	echo -e '\n'
	echo -e 'Setup the local development environment for ${APP_NAME}: \e[36mmake \e[0m\e[1msetup\e[0m'
	echo -e 'Build the front assets: \e[36mmake \e[0m\e[1mbuild-front\e[0m'
	echo -e 'You can run scripts defined in package.json this way: \e[36mmake \e[0m\e[1mrun @script_name\e[0m'
	echo -e 'Stop the running app: \e[36mmake \e[0m\e[1mstop\e[0m'
	echo -e 'Stop the running app and delete the data: \e[36mmake \e[0m\e[1mdestroy\e[0m'
	echo -e 'Start an app that has already been setup: \e[36mmake \e[0m\e[1mstart\e[0m'
	echo -e 'Restart an app that has already been setup: \e[36mmake \e[0m\e[1mrestart\e[0m'
	echo -e 'Update the Drupal installation: \e[36mmake \e[0m\e[1mupdate\e[0m'
	echo -e 'Reload data fixtures: \e[36mmake \e[0m\e[1mreload-fixtures\e[0m'
	echo -e 'Clear the app caches: \e[36mmake \e[0m\e[1mcc\e[0m'
	echo -e 'Start a shell session: \e[36mmake \e[0m\e[1mssh\e[0m'
endef
