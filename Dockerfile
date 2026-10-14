FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends apache2-utils libcurl4-openssl-dev \
    && docker-php-ext-install -j"$(nproc)" curl pdo_mysql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY Frontend/ /var/www/html/
COPY Backend/ /var/www/backend/
COPY docker/apache-autocontrol.conf /etc/apache2/conf-available/autocontrol.conf
COPY docker/entrypoint.sh /usr/local/bin/autocontrol-entrypoint

RUN a2enconf autocontrol \
    && chmod 755 /usr/local/bin/autocontrol-entrypoint \
    && printf 'display_errors=Off\nlog_errors=On\nexpose_php=Off\n' > /usr/local/etc/php/conf.d/autocontrol.ini \
    && printf 'ServerName localhost\n' > /etc/apache2/conf-available/servername.conf \
    && a2enconf servername

EXPOSE 80

ENTRYPOINT ["autocontrol-entrypoint"]
CMD ["apache2-foreground"]
