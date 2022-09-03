FROM php:8.0-apache

RUN apt-get update \
 && apt-get install -y git libcurl4-openssl-dev dnsutils \
 && docker-php-ext-install curl

RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf \
&& sed -i 's!/var/www/html!/var/www!g' /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www