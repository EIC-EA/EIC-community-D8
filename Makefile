include .env

#silent by default
ifndef VERBOSE
.SILENT:
endif

setup:
	$(call do_setup)

test:
	echo -e '\n'
	echo -e '\e[42m${APP_NAME} setup completed\e[0m'

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

import-db:
	docker exec -i ${APP_NAME}_database bash -c 'exec mysql -u${DRUPAL_DATABASE_USERNAME} -p${DRUPAL_DATABASE_PASSWORD} ${DRUPAL_DATABASE_NAME}' < $(FILE)

ssh:
	docker exec -it --user web ${APP_NAME}_web bash

help:
	$(call do_display_commands)

info:
	$(call do_display_app_info)

define do_db_healthcheck
	docker exec -it ${APP_NAME}_web bash -c 'chmod +x /app/docker/web/database-healthcheck.sh'
	docker exec -it ${APP_NAME}_web bash -c '/app/docker/web/database-healthcheck.sh'
endef

define do_setup
	echo -e 'Setting up ${APP_NAME}...'
	docker-compose up -d --build
	docker exec -it ${APP_NAME}_web bash -c 'composer install --no-progress'
	$(call do_db_healthcheck)
	docker exec -it ${APP_NAME}_web bash -c  'drush site-install minimal --site-name=${APP_NAME} --account-name=${DRUPAL_ACCOUNT_USERNAME} --account-pass=${DRUPAL_ACCOUNT_PASSWORD} --existing-config -y'
	docker exec -it ${APP_NAME}_web bash -c  'drush cr'
	echo -e '\n'
	echo -e '\e[42m${APP_NAME} setup completed\e[0m'
	$(call do_display_app_info)
	$(call do_display_commands)
endef

define do_start
	echo -e 'Starting ${APP_NAME}...'
	docker-compose up -d
	$(call do_db_healthcheck)
	docker exec -it ${APP_NAME}_web bash -c  'drush cim -y'
	docker exec -it ${APP_NAME}_web bash -c  'drush cr'
	echo -e '\n'
	echo -e '\e[42m${APP_NAME} started\e[0m'
	$(call do_display_app_info)
	$(call do_display_commands)
endef

define do_restart
	echo -e 'Restarting ${APP_NAME}...'
	docker-compose down
	docker-compose up -d
	$(call do_db_healthcheck)
	docker exec -it ${APP_NAME}_web bash -c  'drush cr'
	echo -e '\n'
	echo -e '\e[42m${APP_NAME} restarted\e[0m'
	$(call do_display_app_info)
	$(call do_display_commands)
endef

define do_update
	echo -e 'Updating ${APP_NAME}...'
	docker exec -it ${APP_NAME}_web bash -c 'composer install --no-progress'
	docker exec -it ${APP_NAME}_web bash -c  'drush cr'
	docker exec -it ${APP_NAME}_web bash -c  'drush cim -y'
	docker exec -it ${APP_NAME}_web bash -c 'drush updb -y'
	docker exec -it ${APP_NAME}_web bash -c  'drush cr'
	echo -e '\n'
	echo -e '\e[42m${APP_NAME} updated\e[0m'
endef

define do_cc
	echo -e 'Clearing ${APP_NAME} caches...'
	docker exec -it ${APP_NAME}_web bash -c 'drush cr'
endef

define do_stop
	echo -e 'Stopping ${APP_NAME}...'
	docker-compose down
	echo -e '\n'
	echo -e '\e[42m${APP_NAME} stopped\e[0m'
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
	$(if (${APP_PORT},443),echo -e 'APP URL: \e[36mhttp://localhost\e[0m\nAdmin user credentials: ${DRUPAL_ADMIN_USER} - ${DRUPAL_ADMIN_PASSWORD}\nDatabase port: ${DB_PORT}',echo -e 'APP URL: \e[36mhttps://localhost:${APP_PORT}\e[0m\nAdmin user credentials: ${DRUPAL_ADMIN_USER} - ${DRUPAL_ADMIN_PASSWORD}\nDatabase port: ${DB_PORT}')
	echo -e 'To resolve the browser SSL errors, exceute the following commands:\n'
endef

define do_display_commands
	echo -e '\n'
	echo -e '--- AVAILABLE COMMANDS ---'
	echo -e '\n'
	echo -e 'Setup the local development environment for ${APP_NAME}: \e[36mmake \e[0m\e[1msetup\e[0m'
	echo -e 'Stop the running app: \e[36mmake \e[0m\e[1mstop\e[0m'
	echo -e 'Stop the running app and delete the data: \e[36mmake \e[0m\e[1mdestroy\e[0m'
	echo -e 'Start an app that has already been setup: \e[36mmake \e[0m\e[1mstart\e[0m'
	echo -e 'Restart an app that has already been setup: \e[36mmake \e[0m\e[1mrestart\e[0m'
	echo -e 'Update the Drupal installation: \e[36mmake \e[0m\e[1mupdate\e[0m'
	echo -e 'Clear the app caches: \e[36mmake \e[0m\e[1mcc\e[0m'
	echo -e 'Start a shell session: \e[36mmake \e[0m\e[1mssh\e[0m'
endef
