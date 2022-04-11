#!/usr/bin/env sh

if [ -z "$KUBERNETES_PORT" ] || [ -z "$COMPOSE" ]; then
  /usr/local/sbin/php-fpm --nodaemonize
else
  /usr/local/bin/php -S 0.0.0.0:80
fi
