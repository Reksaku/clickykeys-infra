FROM php:8.5-fpm-alpine

# PDO + MySQL
RUN docker-php-ext-install pdo pdo_mysql

# MaxMind DB reader extension (provides the MaxMind\Db\Reader class natively,
# so no Composer dependency is needed at runtime).
RUN apk add --no-cache libmaxminddb \
 && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS libmaxminddb-dev \
 && pecl install maxminddb \
 && docker-php-ext-enable maxminddb \
 && apk del .build-deps

COPY web/db_php/ /var/www/html/db_php/
COPY web/api/    /var/www/html/api/

# GeoIP database (DB-IP Country Lite, MMDB format). See geoip/README.md
# for how to obtain/refresh it. Path is overridable via GEOIP_DB_PATH.
COPY geoip/ /var/www/geoip/

# php-fpm on port 9000
EXPOSE 9000
