FROM nginx:stable-alpine

VOLUME /var/www/html
WORKDIR /var/www/html

COPY ./ /var/www/html
COPY ./docker/nginx/* /etc/nginx/conf.d/