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

* [Docker Compose](https://docs.docker.com/compose/install)
* Make
* PHP 7.2 or greater (needed to run [GrumPHP](https://github.com/phpro/grumphp) Git hooks)

## Running locally

This application doesn't use Open Europa's default docker-compose configuration.
To run to project please follow these steps:
- (macOS users) Please read [Using Docker on macOS](#using-docker-on-macos) before
- Copy and rename `.env.docker` to `.env`
- (First time only) Setup the dev environment wih the following command:
````bash
make setup
# If you want to run frontend install and builds
make build-front
````

### Update your existing local environment
- Run `make update`

### Using Docker on macOS
In order to maximise the performance of using Docker on macOS, we strongly advise using at least version 18.03.1 of Docker which supports native NFS integration. You can find a very comprehensive article about this topic under following link [Set Up Docker For Mac with Native NFS](https://medium.com/@sean.handley/how-to-set-up-docker-for-mac-with-native-nfs-145151458adc).

Before running the `make setup` command, be sure that NFS is configured correctly. If not, run the bash script in order to configure Docker native NFS support.
You can find the source of a script in the article or by using this link [setup_native_nfs_docker_osx.sh](https://gist.githubusercontent.com/seanhandley/7dad300420e5f8f02e7243b7651c6657/raw/fdd77fe66cf9ce893fa0175d735cbede2bb065e4/setup_native_nfs_docker_osx.sh).

Please note that this script will use /Users by default, in some cases this might not fit. You can change it (Line 53) to use a sub-directory where applications using docker are or this project's path ($PWD).
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
