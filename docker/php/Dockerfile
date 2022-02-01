ARG PHP_VERSION=7.3

FROM php:${PHP_VERSION}-fpm

ARG UID=1000
ARG GID=1000
ARG DRUSH_VERSION=8

ENV DEBIAN_FRONTEND noninteractive

RUN apt-get -o Acquire::Check-Valid-Until=false -o Acquire::Check-Date=false update \
  && apt-get -y install apt-transport-https apt-utils build-essential xorg \
    libssl-dev libxrender-dev wget gdebi \
    libzip-dev libpng-dev libjpeg-dev libfreetype6-dev \
    zip unzip libicu-dev g++ git

# Install extensions
RUN docker-php-ext-configure gd --with-jpeg-dir=/usr/include --with-freetype-dir=/usr/include \
  && docker-php-ext-install gd \
  && docker-php-ext-install bcmath \
  && docker-php-ext-install zip \
  && docker-php-ext-install opcache \
  && pecl install -o -f redis \
  && docker-php-ext-enable redis \
  && docker-php-ext-configure intl \
  && docker-php-ext-install intl

RUN apt-get update -y; \
    apt-get install mariadb-client -y; \
    pecl install xdebug apcu; \
    docker-php-ext-install pdo_mysql; \
    docker-php-ext-enable xdebug apcu; \
    pecl clear-cache;

# Install composer
# https://github.com/drupal/drupal/blob/9.0.1/composer.lock#L4052-L4053
COPY --from=composer:1.10 /usr/bin/composer /usr/local/bin/

# Change the fpm user
RUN groupadd -o -g ${GID} web \
  && useradd -d /var/www/ -s /usr/sbin/nologin -u ${UID} -g ${GID} -m -d /home/web web \
  && sed -i s/'user = www-data'/'user = web'/g /usr/local/etc/php-fpm.d/www.conf \
  && sed -i s/'group = www-data'/'group = web'/g /usr/local/etc/php-fpm.d/www.conf \
  && chown -R web:web /var/www

# Everything below this should be moved to a future *-dev image
# Install drush launcher
RUN latest_drush_version=$(curl -s https://api.github.com/repos/drush-ops/drush/releases | grep tag_name | grep ${DRUSH_VERSION}'\.' | head -1 | awk -F '"' '{print $4}') \
  && curl -s -L https://github.com/drush-ops/drush/releases/download/${latest_drush_version}/drush.phar -o /usr/local/bin/drush \
  && chmod +x /usr/local/bin/drush \
  # smoke test
  && drush version

ENV YARN_VERSION=1.22.15
COPY --from=node:14 /usr/local/lib/node_modules /usr/local/lib/node_modules
COPY --from=node:14 /usr/local/bin/node /usr/local/bin/node
RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
  && ln -s /usr/local/lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx \
  && wget https://github.com/yarnpkg/yarn/releases/download/v${YARN_VERSION}/yarn_${YARN_VERSION}_all.deb \
  && dpkg -i yarn_${YARN_VERSION}_all.deb \
  # smoke test
  && node --version \
  && npm --version \
  && yarn --version

USER web

CMD ["php-fpm"]
