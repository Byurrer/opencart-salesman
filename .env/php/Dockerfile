FROM php:5.6-fpm

COPY xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini
COPY php.ini /usr/local/etc/php/conf.d/php.ini

RUN apt update && apt install -y libzip-dev zip mc nano libfreetype6-dev libjpeg62-turbo-dev libpng-dev libmcrypt4 libmcrypt-dev \
    && docker-php-ext-install pdo_mysql mysqli zip \
    && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-install gd \
    && pecl install xdebug-2.5.5 && docker-php-ext-enable xdebug \
    && docker-php-ext-install mcrypt

WORKDIR /var/www/html

CMD ["php-fpm"]
