FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends $PHPIZE_DEPS libssl-dev pkg-config \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb \
    && apt-get purge -y --auto-remove $PHPIZE_DEPS \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY . /var/www/html

RUN set -eux; \
    if [ -f /var/www/html/composer.json ] && [ -f /var/www/html/index.php ]; then \
        APP_DIR="/var/www/html"; \
    elif [ -f /var/www/html/voting_system/composer.json ] && [ -f /var/www/html/voting_system/index.php ]; then \
        APP_DIR="/var/www/html/voting_system"; \
    else \
        echo "Unable to locate application root in build context."; \
        find /var/www/html -maxdepth 3 -type f | head -n 120; \
        exit 1; \
    fi; \
    cd "${APP_DIR}"; \
    composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader --ignore-platform-req=ext-mongodb; \
    if [ "${APP_DIR}" != "/var/www/html" ]; then \
        cp -a "${APP_DIR}/." /var/www/html/; \
    fi; \
    mkdir -p /var/www/html/assets/images/candidates; \
    chown -R www-data:www-data /var/www/html/assets/images/candidates

CMD ["sh", "-c", "set -eu; PORT_TO_USE=\"${PORT:-10000}\"; sed -E -i \"s/^Listen [0-9]+$/Listen ${PORT_TO_USE}/\" /etc/apache2/ports.conf; sed -E -i \"s#<VirtualHost \\\\*:[0-9]+>#<VirtualHost *:${PORT_TO_USE}>#\" /etc/apache2/sites-available/000-default.conf; apache2ctl -t; exec apache2-foreground"]
