FROM debian:latest

RUN apt-get -o Acquire::Check-Valid-Until=false -o Acquire::Check-Date=false update -y \
    && apt-get -y install apache2

# Enable apache modules
RUN a2enmod rewrite headers \
    && a2enmod proxy_fcgi \
    && a2enmod rewrite \
    && a2enmod ssl;

RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

WORKDIR /app
EXPOSE 80
COPY apache-foreground /usr/local/bin/
RUN chmod +x /usr/local/bin/apache-foreground

CMD ["apache-foreground"]


