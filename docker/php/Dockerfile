FROM php:7.3-fpm

ENV DEBIAN_FRONTEND noninteractive
ARG DRUSH_VERSION=10

RUN apt-get update \
  && apt-get -y dist-upgrade \
  && apt-get -y install apt-transport-https apt-utils build-essential xorg libssl-dev libxrender-dev wget gdebi

# Install extensions
RUN apt-get -y install libzip-dev libpng-dev zip unzip libicu-dev g++ git \
  && docker-php-ext-install gd \
  && docker-php-ext-install bcmath \
  && docker-php-ext-install zip \
  && docker-php-ext-install opcache \
  && docker-php-ext-configure intl \
  && docker-php-ext-install intl

# Install composer
# https://github.com/drupal/drupal/blob/9.0.1/composer.lock#L4052-L4053
COPY --from=composer:1.10 /usr/bin/composer /usr/local/bin/

# Change PHP-FPM user
RUN groupadd -g 1001 web \
  && useradd -d /var/www/ -s /usr/sbin/nologin -u 1000 -g 1001 web \
  && sed -i s/'user = www-data'/'user = web'/g /usr/local/etc/php-fpm.d/www.conf \
  && sed -i s/'group = www-data'/'group = web'/g /usr/local/etc/php-fpm.d/www.conf

RUN apt-get update -y; \
  apt-get install mariadb-client -y; \
  pecl install xdebug apcu; \
  docker-php-ext-install pdo_mysql intl; \
  docker-php-ext-enable xdebug apcu intl; \
  pecl clear-cache;

COPY dev.ini /usr/local/etc/php/conf.d/dev.ini

#Install drush launcher
RUN DRUSH_VERSION=$(curl -s https://api.github.com/repos/drush-ops/drush/releases | grep tag_name | grep '$DRUSH_VERSION\.' | head -1 | awk -F '"' '{print $4}')
ADD https://github.com/drush-ops/drush-launcher/releases/download/$DRUSH_VERSION/drush.phar drush.phar
RUN chmod +x drush.phar && mv drush.phar /usr/local/bin/drush
