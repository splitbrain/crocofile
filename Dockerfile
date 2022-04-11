FROM php:7-fpm-alpine

ADD . /app

WORKDIR /app

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

ENV UPLOAD_LIMIT=500M

RUN touch /usr/local/etc/php/conf.d/upload_limit.ini \
    && echo "upload_max_filesize = $UPLOAD_LIMIT;" >> /usr/local/etc/php/conf.d/upload_limit.ini \
    && echo "post_max_size = $UPLOAD_LIMIT;" >> /usr/local/etc/php/conf.d/upload_limit.ini

USER www-data

ENTRYPOINT /app/docker/run.sh
