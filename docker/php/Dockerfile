ARG PHP_VERSION=7.4
ARG COMPOSER_VERSION=1

FROM composer:${COMPOSER_VERSION} AS composer
FROM php:${PHP_VERSION}-fpm

ARG UID=1000
ARG GID=1000
ENV DEBIAN_FRONTEND noninteractive

RUN apt-get -o Acquire::Check-Valid-Until=false -o Acquire::Check-Date=false update \
  && apt-get -y install apt-transport-https apt-utils build-essential xorg \
    libssl-dev libxrender-dev wget gdebi \
    libzip-dev libpng-dev libjpeg-dev libfreetype6-dev \
    zip unzip libicu-dev g++ git

# Install extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
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

# Change the fpm user
RUN groupadd -o -g ${GID} web \
  && useradd -d /var/www/ -s /usr/sbin/nologin -u ${UID} -g ${GID} -m -d /home/web web \
  && sed -i s/'user = www-data'/'user = web'/g /usr/local/etc/php-fpm.d/www.conf \
  && sed -i s/'group = www-data'/'group = web'/g /usr/local/etc/php-fpm.d/www.conf \
  && chown -R web:web /var/www


COPY --from=composer /usr/bin/composer /usr/local/bin/

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
