#!/bin/sh
set -e

echo 'Starting debugger'
#start debugging daemon with root
/etc/init.d/tideways-daemon start

# change user to web and start php-fpm
echo 'Starting php-fpm with user:'
echo $(whoami)
php-fpm
