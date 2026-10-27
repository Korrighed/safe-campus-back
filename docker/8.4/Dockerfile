FROM php:8.4-cli

# UID/GID de l'utilisateur hote : l'utilisateur du conteneur est cree avec les
# memes identifiants, les fichiers ecrits dans le bind mount lui appartiennent.
ARG WWWUSER=1000
ARG WWWGROUP=1000
ARG APP_PORT=8000

WORKDIR /var/www/html

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y --no-install-recommends \
        git curl zip unzip ca-certificates gnupg sudo postgresql-client \
        libpq-dev libpng-dev libjpeg-dev libfreetype6-dev libicu-dev libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_pgsql gd bcmath intl pcntl zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN groupadd -g ${WWWGROUP} scback \
    && useradd -ms /bin/bash -g scback -u ${WWWUSER} scback \
    && echo 'scback ALL=(ALL) NOPASSWD: ALL' > /etc/sudoers.d/scback

COPY docker/8.4/php.ini /usr/local/etc/php/conf.d/99-app.ini
COPY docker/8.4/start-container /usr/local/bin/start-container
RUN chmod +x /usr/local/bin/start-container

USER scback

EXPOSE ${APP_PORT}

# Sans argument, start-container lance composer install puis artisan serve
ENTRYPOINT ["start-container"]
