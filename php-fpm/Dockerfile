FROM php:8.2-fpm-alpine

# PDO + MySQL
RUN docker-php-ext-install pdo pdo_mysql

COPY web/db_php/ /var/www/html/db_php/
COPY web/api/    /var/www/html/api/

# php-fpm on port 9000
EXPOSE 9000
