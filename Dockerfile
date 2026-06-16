# syntax=docker/dockerfile:1

############################################
# Imagem base PHP-FPM
############################################
FROM php:8.3-fpm-alpine AS base

# Argumentos para criar um usuário sem privilégios alinhado ao host
ARG UID=1000
ARG GID=1000

# Dependências de sistema
RUN apk add --no-cache \
        bash \
        git \
        curl \
        zip \
        unzip \
        icu-dev \
        libzip-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        oniguruma-dev \
        postgresql-dev \
        $PHPIZE_DEPS

# Extensões PHP necessárias para o Laravel + PostgreSQL
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo \
        pdo_pgsql \
        pgsql \
        intl \
        zip \
        gd \
        bcmath \
        opcache \
        pcntl

# Redis via PECL (opcional, útil para cache/filas)
RUN pecl install redis \
    && docker-php-ext-enable redis

# Composer (copiado da imagem oficial)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Cria um usuário não-root para rodar a aplicação
RUN addgroup -g ${GID} app \
    && adduser -u ${UID} -G app -s /bin/bash -D app

# Configuração de PHP recomendada para produção (pode ser sobrescrita)
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

WORKDIR /var/www/html

# Copia código da aplicação (no dev é sobrescrito pelo volume do compose)
COPY --chown=app:app . /var/www/html

# Instala dependências caso exista um composer.json (ignora se ainda não houver app)
RUN if [ -f composer.json ]; then \
        composer install --no-interaction --prefer-dist --no-progress --no-scripts; \
    fi

USER app

EXPOSE 9000

CMD ["php-fpm"]
