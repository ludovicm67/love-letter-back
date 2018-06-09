FROM ubuntu:18.04

LABEL version="1.0"
LABEL description="LoveLetter Backend"

# installing dependencies
RUN ln -fs /usr/share/zoneinfo/Europe/Paris /etc/localtime \
  && apt-get -y update \
  && apt-get install -y --no-install-recommends \
    git \
    unzip \
    curl \
    apache2 \
    php \
    libapache2-mod-php \
    php-json \
    php-mysql \
    php-mbstring \
    php-xdebug \
    php-xml \
  && curl -sL https://deb.nodesource.com/setup_10.x | bash - \
  && apt-get install -y --no-install-recommends nodejs npm \
  && rm -rf /var/lib/apt/lists/* \
  && npm config set registry http://registry.npmjs.org/ \
  && npm install -g npm laravel-echo-server

# configuring Apache
RUN sed -i 's!/var/www/html!/var/www/html/public!g' \
    /etc/apache2/apache2.conf \
    /etc/apache2/sites-available/000-default.conf \
  && sed -i 's!AllowOverride None!AllowOverride All!g' \
    /etc/apache2/apache2.conf \
    /etc/apache2/sites-available/000-default.conf \
  && sed -i 's!display_errors = Off!display_errors = On!g' \
    /etc/php/7.2/apache2/php.ini \
  && a2enmod rewrite \
  && a2enmod headers

# installing composer (for installing php dependencies)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY ./.env.docker /var/www/html/
COPY ./composer.json /var/www/html/
COPY ./composer.lock /var/www/html/
COPY ./artisan /var/www/html/
COPY ./app /var/www/html/app
COPY ./config /var/www/html/config
COPY ./bootstrap /var/www/html/bootstrap
COPY ./database /var/www/html/database
COPY ./routes /var/www/html/routes

RUN export COMPOSER_ALLOW_SUPERUSER=1 \
  && cd /var/www/html/ \
  && cp .env.docker .env \
  && composer self-update \
  && composer install --no-interaction \
  && php artisan key:generate \
  && php artisan jwt:secret

COPY . /var/www/html/
RUN sed -i 's/3001/1338/g' /var/www/html/laravel-echo-server.json
RUN chown -R www-data:www-data /var/www/html

# commands to run at startup
CMD sleep 25 \
  && cd /var/www/html/ \
  && php artisan migrate:fresh --seed \
  && vendor/bin/phpunit \
  && (laravel-echo-server start&) \
  && /usr/sbin/apache2ctl -DFOREGROUND
