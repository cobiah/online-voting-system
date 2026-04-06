FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends $PHPIZE_DEPS libssl-dev pkg-config \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb \
    && apt-get purge -y --auto-remove $PHPIZE_DEPS \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY . /tmp/src

RUN set -eux; \
    if [ -f /tmp/src/index.php ] && [ -f /tmp/src/composer.json ]; then \
        APP_SRC="/tmp/src"; \
    elif [ -f /tmp/src/voting_system/index.php ] && [ -f /tmp/src/voting_system/composer.json ]; then \
        APP_SRC="/tmp/src/voting_system"; \
    else \
        echo "Unable to locate application root in build context."; \
        find /tmp/src -maxdepth 2 -type f | head -n 100; \
        exit 1; \
    fi; \
    cp -a "${APP_SRC}/." /var/www/html/; \
    rm -rf /tmp/src; \
    composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader --ignore-platform-req=ext-mongodb; \
    mkdir -p /var/www/html/assets/images/candidates; \
    chown -R www-data:www-data /var/www/html/assets/images/candidates

CMD ["sh", "-c", "set -eu; PORT_TO_USE=\"${PORT:-10000}\"; sed -E -i \"s/^Listen [0-9]+$/Listen ${PORT_TO_USE}/\" /etc/apache2/ports.conf; sed -E -i \"s#<VirtualHost \\\\*:[0-9]+>#<VirtualHost *:${PORT_TO_USE}>#\" /etc/apache2/sites-available/000-default.conf; apache2ctl -t; exec apache2-foreground"]
