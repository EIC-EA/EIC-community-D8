ARG PHP_VERSION=8.1
ARG COMPOSER_VERSION=2

FROM composer:${COMPOSER_VERSION} AS composer
FROM php:${PHP_VERSION}-fpm

ARG UID=1000
ARG GID=1000
ENV DEBIAN_FRONTEND noninteractive
ARG DRUSH_LAUNCHER_VERSION=8
ARG DRUSH_VERSION=8.4.12

RUN apt-get -o Acquire::Check-Valid-Until=false -o Acquire::Check-Date=false update \
  && apt-get -y install apt-transport-https apt-utils build-essential xorg \
    libssl-dev libxrender-dev wget gdebi \
    libzip-dev libpng-dev libjpeg-dev libfreetype6-dev \
    zip unzip libicu-dev g++ git libxml2-dev

#Install dependencies for cypress
RUN apt-get -y install libgtk2.0-0 libgtk-3-0 libgbm-dev libnotify-dev libgconf-2-4 libnss3 libxss1 libasound2 libxtst6 xauth xvfb

# Install extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
  && docker-php-ext-install gd \
  && docker-php-ext-install bcmath \
  && docker-php-ext-install zip \
  && docker-php-ext-install opcache \
  && pecl install -o -f redis \
  && docker-php-ext-enable redis \
  && docker-php-ext-configure intl \
  && docker-php-ext-install intl \
  && docker-php-ext-install xml

RUN apt-get update -y; \
    apt-get install mariadb-client -y; \
    pecl install apcu; \
    docker-php-ext-install pdo_mysql; \
    docker-php-ext-enable apcu; \
    pecl clear-cache;

# Change the fpm user
RUN groupadd -o -g ${GID} web \
  && useradd -d /var/www/ -s /usr/sbin/nologin -u ${UID} -g ${GID} -m -d /home/web web \
  && sed -i s/'user = www-data'/'user = web'/g /usr/local/etc/php-fpm.d/www.conf \
  && sed -i s/'group = www-data'/'group = web'/g /usr/local/etc/php-fpm.d/www.conf \
  && chown -R web:web /var/www

RUN echo 'pm.max_children = 10' >> /usr/local/etc/php-fpm.d/zz-docker.conf

COPY --from=composer /usr/bin/composer /usr/local/bin/

#RUN latest_drush_version=$(curl -s https://api.github.com/repos/drush-ops/drush/releases | grep tag_name | grep ${DRUSH_LAUNCHER_VERSION}'\.' | head -1 | awk -F '"' '{print $4}') \
RUN latest_drush_version=${DRUSH_VERSION} \
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
  && yarn --version;

RUN apt update && apt install -y unattended-upgrades;
RUN dpkg-reconfigure unattended-upgrades;
RUN unattended-upgrade -d;
RUN apt-get install -yq --no-install-recommends gnupg2 curl ca-certificates
RUN apt-get autoremove unattended-upgrades -y --purge; \
    apt-get clean; \
    rm -rf /var/lib/apt/lists/*;

USER web

CMD ["php-fpm"]
