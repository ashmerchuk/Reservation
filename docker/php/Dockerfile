FROM php:8.2-fpm-alpine

ARG IS_DEV=0

VOLUME /var/www/html
WORKDIR /var/www/html
COPY ./ /var/www/html

#update
RUN apk update && apk upgrade
RUN apk add --update linux-headers

ENV PHP_OPCACHE_VALIDATE_TIMESTAMPS="0"

COPY docker/php/conf.d/ /usr/local/etc/php/conf.d/
COPY docker/php/php-fpm.d/ /usr/local/etc/php-fpm.d/

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN composer global require 'phpmetrics/phpmetrics'

# miscelanious
RUN apk add vim htop openssh-client git zip sudo

# xdebug / coverage
RUN apk --update --no-cache add autoconf g++ make \
    && pecl install xdebug \
    && apk del --purge autoconf g++ make

# mysql
RUN docker-php-ext-install mysqli pdo_mysql

# opcache
RUN docker-php-ext-install opcache

# internationalization
RUN apk add --no-cache --virtual .build-deps icu-dev \
    && docker-php-ext-install intl

# clear cache
RUN rm -rf /var/cache/apk/*



RUN if [ "$IS_DEV" != "0" ]; \
    then \
       echo '-- INSTALLING DEV MODE --'; \
       docker-php-ext-enable xdebug; \
       cp -rf /var/www/html/docker/php/development/*.ini $PHP_INI_DIR/conf.d/; \
   fi;