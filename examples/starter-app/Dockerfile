FROM dunglas/frankenphp:1-php8.5-alpine

WORKDIR /app

RUN install-php-extensions pdo_pgsql opcache

COPY . /app
COPY examples/starter-app/Caddyfile /etc/caddy/Caddyfile

RUN adduser -D -u 1000 kongono && chown -R kongono:kongono /app /config /data
USER kongono

ENV SERVER_NAME=:8080
EXPOSE 8080
