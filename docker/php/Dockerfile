FROM php:8.5-fpm

WORKDIR /app

RUN apt-get update && apt-get install -y \
    gnupg g++ procps openssl git unzip zlib1g-dev libzip-dev \
    libfreetype6-dev libpng-dev libjpeg-dev libicu-dev libonig-dev libxslt1-dev acl \
    librabbitmq-dev libpq-dev \
 && docker-php-ext-configure gd --with-jpeg --with-freetype \
 && docker-php-ext-install -j$(nproc) mysqli pdo pdo_mysql pdo_pgsql pgsql zip xsl gd intl exif mbstring \
 && pecl install amqp \
 && docker-php-ext-enable amqp pdo_mysql \
 && pecl install xdebug \
 && docker-php-ext-enable xdebug \
 && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN cp /usr/local/etc/php/php.ini-development /usr/local/etc/php/php.ini

RUN echo 'alias sf="php bin/console"' >> ~/.bashrc

# Set timezone
RUN ln -snf /usr/share/zoneinfo/${TIMEZONE} /etc/localtime && echo ${TIMEZONE} > /etc/timezone \
    && printf '[PHP]\ndate.timezone = "%s"\n', ${TIMEZONE} > /usr/local/etc/php/conf.d/tzone.ini \
    && "date"

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN ["php","-v"]
