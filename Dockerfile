FROM php:7-fpm-buster

COPY ./ /app/
RUN chown www-data:www-data -R /app/

VOLUME /app

RUN docker-php-ext-install mysqli
RUN docker-php-ext-install gettext

RUN apt-get update && apt-get install -y zlib1g-dev libicu-dev g++ locales

RUN docker-php-ext-configure intl
RUN docker-php-ext-install intl

RUN sed -i -e 's/# en_GB.UTF-8 UTF-8/en_GB.UTF-8 UTF-8/' /etc/locale.gen && \
    sed -i -e 's/# de_DE.UTF-8 UTF-8/de_DE.UTF-8 UTF-8/' /etc/locale.gen && \
    sed -i -e 's/# pl_PL.UTF-8 UTF-8/pl_PL.UTF-8 UTF-8/' /etc/locale.gen && \
    dpkg-reconfigure --frontend=noninteractive locales && \
    update-locale LANG=en_GB.UTF-8
