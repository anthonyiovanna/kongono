FROM dunglas/frankenphp:1-php8.5-alpine

WORKDIR /app

RUN install-php-extensions pdo_pgsql opcache

COPY . /app
COPY examples/starter-app/Caddyfile /etc/caddy/Caddyfile

ENV SERVER_NAME=:8080
EXPOSE 8080
