FROM php:8.3.15-apache AS base
RUN apt-get update && apt-get install -y supervisor
WORKDIR /var/www/html
ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN install-php-extensions intl
RUN install-php-extensions opcache
RUN install-php-extensions zip
RUN install-php-extensions pdo_pgsql
RUN install-php-extensions @composer
ENV COMPOSER_ALLOW_SUPERUSER=1
COPY ./.docker/apache/apache.conf /etc/apache2/sites-available/000-default.conf
RUN a2enmod rewrite
COPY ./.docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
ENTRYPOINT [ "/usr/local/bin/entrypoint.sh" ]

FROM base AS dev
ENV APP_ENV=dev
RUN install-php-extensions pcov
RUN cp $PHP_INI_DIR/php.ini-development $PHP_INI_DIR/php.ini
RUN sh -c "$(curl --location https://taskfile.dev/install.sh)" -- -d -b  /usr/local/bin
ADD --chmod=0755 ./.docker/entrypoint.dev.sh /usr/local/bin/entrypoint.sh

FROM base AS prod
ENV APP_ENV=prod
ENV APP_DEBUG=0
RUN cp $PHP_INI_DIR/php.ini-production $PHP_INI_DIR/php.ini
COPY . .
ADD --chmod=0755 ./.docker/entrypoint.prod.sh /usr/local/bin/entrypoint.sh
RUN composer install --no-dev --optimize-autoloader --no-interaction
RUN php bin/console cache:clear && php bin/console cache:warmup
RUN composer dump-env prod --empty
RUN chown -R www-data:www-data ./var
EXPOSE 80
