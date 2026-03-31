FROM composer:2 AS composer_deps

WORKDIR /app
COPY composer.json /app/composer.json
RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader --ignore-platform-req=ext-mongodb

FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends $PHPIZE_DEPS libssl-dev pkg-config \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb \
    && apt-get purge -y --auto-remove $PHPIZE_DEPS \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY . /var/www/html
COPY --from=composer_deps /app/vendor /var/www/html/vendor

RUN mkdir -p /var/www/html/assets/images/candidates
RUN chown -R www-data:www-data /var/www/html/assets/images/candidates

COPY docker/render-entrypoint.sh /usr/local/bin/render-entrypoint.sh
RUN chmod +x /usr/local/bin/render-entrypoint.sh

ENTRYPOINT ["/usr/local/bin/render-entrypoint.sh"]
CMD ["apache2-foreground"]
