ARG PHP_VERSION=7.4

FROM php:${PHP_VERSION}-fpm

ARG UID=1000
ARG GID=1000
ARG DRUSH_VERSION=8

ENV DEBIAN_FRONTEND noninteractive

# Enable secure repository
RUN apt-get update \
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

# Change the fpm user
RUN groupadd -g ${GID} web \
  && useradd -d /var/www/ -s /usr/sbin/nologin -u ${UID} -g ${GID} web \
  && sed -i s/'user = www-data'/'user = web'/g /usr/local/etc/php-fpm.d/www.conf \
  && sed -i s/'group = www-data'/'group = web'/g /usr/local/etc/php-fpm.d/www.conf

RUN apt-get update -y; \
    apt-get install mariadb-client -y; \
    pecl install xdebug apcu; \
    docker-php-ext-install pdo_mysql intl; \
    docker-php-ext-enable xdebug apcu intl; \
    pecl clear-cache;

#Install drush launcher
RUN latest_drush_version=$(curl -s https://api.github.com/repos/drush-ops/drush/releases | grep tag_name | grep ${DRUSH_VERSION}'\.' | head -1 | awk -F '"' '{print $4}') \
  && curl -s -L https://github.com/drush-ops/drush/releases/download/${latest_drush_version}/drush.phar -o /usr/local/bin/drush \
  && chmod +x /usr/local/bin/drush

CMD ["php-fpm"]
