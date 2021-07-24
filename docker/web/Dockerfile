FROM fpfis/httpd-php-dev:7.3

ARG UID=1000
ARG GID=1000

RUN groupadd -g ${UID} web \
  && useradd -d /var/www/ -s /usr/sbin/nologin -u ${UID} -g ${GID} web \
  && usermod -aG sudo web

USER web
