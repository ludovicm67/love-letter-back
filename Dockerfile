FROM ubuntu:17.10

MAINTAINER Ludovic Muller <ludovicmuller1@gmail.com>

LABEL version="1.0"
LABEL description="LoveLetter Backend"

# installing dependencies
RUN apt-get -y update
RUN apt-get install -y \
  apt-utils \
  git \
  unzip \
  curl \
  apache2 \
  php \
  libapache2-mod-php \
  php-gd \
  php-json \
  php-mysql \
  php-mcrypt \
  php-mbstring \
  php-xdebug \
  php-xml \
  mcrypt

# configuring Apache
RUN sed -i 's!/var/www/html!/var/www/html/public!g' \
  /etc/apache2/apache2.conf \
  /etc/apache2/sites-available/000-default.conf
RUN sed -i 's!AllowOverride None!AllowOverride All!g' \
  /etc/apache2/apache2.conf \
  /etc/apache2/sites-available/000-default.conf
RUN a2enmod rewrite

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
  && composer install \
  && cp .env.docker .env \
  && composer self-update \
  && composer install --no-interaction \
  && php artisan key:generate \
  && php artisan jwt:secret

COPY . /var/www/html/

# commands to run at startup
CMD sleep 25 \
  && cd /var/www/html/ \
  && php artisan migrate:fresh --seed \
  && /usr/sbin/apache2ctl -DFOREGROUND
