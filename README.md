# EIC-community-D8

The EIC community Drupal 8 Platform is based on OpenEuropa Drupal codebase using
[OpenEuropa components](https://github.com/openeuropa/documentation/blob/master/docs/openeuropa-components.md). It comes
with installation of two components:

- [The OpenEuropa Profile](https://github.com/openeuropa/oe_profile):
  a lightweight Drupal installation profile that includes the minimal number of modules to help get your started
- [The OpenEuropa Theme](https://github.com/openeuropa/oe_theme): the official Drupal 8 theme of the European Commission
  which will ensure that the project complies with
  the [European Component Library](https://github.com/ec-europa/europa-component-library)
  guidelines.

In order to build the functionality of the website you are free to use any of the
[OpenEuropa components](https://github.com/openeuropa/openeuropa/blob/master/docs/openeuropa-components.md).

## Prerequisites

You need to have the following software installed on your local development environment:

* [Docker Compose](https://docs.docker.com/compose/install/)
* Make
* PHP 7.2 or greater (needed to run [GrumPHP](https://github.com/phpro/grumphp) Git hooks)

## Site build and installation

This application doesn't use Open Europa's default docker-compose configuration.
To run to project please follow these steps:
- Copy and rename `.env.docker` to `.env`
- (First time only) Setup the dev environment wih the following command:
````bash
make setup
# If you want to run frontend install and builds
make build-front
````

Before to commit your project on your repository, export the configuration on `config/sync`
using the following command:

```bash
docker-compose exec php ./vendor/bin/drush cex
```

## Running the tests

To run the coding standards and other static checks:

```bash
docker-compose exec php ./vendor/bin/grumphp run
```

To run Behat tests:

```bash
docker-compose exec php ./vendor/bin/behat
```

## Troubleshooting

If you run `composer install` for the first time from within the Docker container GrumPHP will set its Git hook paths to
the container's ones.

If you get such error messages reinitialize GrumPHP paths on your host machine
(meaning, outside the container) by running:

```bash
./vendor/bin/grumphp git:deinit
./vendor/bin/grumphp git:init
```
## Deploy STAG site on Blue4you hosting (https://eic-d8.stg.blue4you.be)

TLDR; Command:

```shell
sh scripts/deploy.sh [BRANCH|-g] [-b] [-i] [-m PASSWORD]
```

### Deploy steps

#### Step 1: Backup database (and drop database for re-install)

Backup the database and drop all tables. Keep the empty database for the new installation.

#### Step 2: Open a virtual terminal session with `screen` (optional, but highly recommended)

Screen or GNU Screen is a terminal multiplexer. In other words, it means that you can start a screen session and then
open any number of windows (virtual terminals) inside that session. Processes running in Screen will continue to run
when their window is not visible even if you get disconnected.

Open a **new virtual terminal session**. (change the git branch in the name to make it easily identifiable)

```shell
screen -S deploy-update-GIT_BRANCH
```

If you want to check the progress of the script after closing your terminal, you can **resume the virtual terminal
session**.

```shell
screen -R deploy-install-GIT_BRANCH
```

#### Step 3: Run deploy script

SSH into the staging server, go to the projects root directory `/home/blue4you/websites/eic-d8.stg.blue4you.be/www` and
execute the deploy script.

```shell
sh scripts/deploy.sh [BRANCH|-g] [-b] [-i] [-m PASSWORD]
```
