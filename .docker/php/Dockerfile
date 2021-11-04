FROM php:8.0.12-fpm

RUN apt-get update && apt-get install -y git libfreetype6-dev libjpeg62-turbo-dev libpng-dev libmemcached-dev zlib1g-dev zip unzip
RUN pecl install xdebug && docker-php-ext-enable xdebug
RUN docker-php-ext-install gd && docker-php-ext-enable gd
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli