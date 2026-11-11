FROM php:8.3-cli-alpine

# Install build & runtime dependencies, OCR, and build tools
RUN apk add --no-cache \
    bash \
    postgresql-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    icu-dev \
    tesseract-ocr \
    tesseract-ocr-data-ind \
    poppler-utils \
    git \
    unzip \
    nodejs \
    npm \
    linux-headers \
    $PHPIZE_DEPS

# Configure and install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_pgsql \
        pgsql \
        zip \
        gd \
        intl \
        bcmath \
        pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS

# Copy Composer from official image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Configure user
ARG PUID=1000
ARG PGID=1000
RUN addgroup -g ${PGID} appuser && \
    adduser -D -u ${PUID} -G appuser appuser

WORKDIR /var/www/html

# Install dependencies inside image (tanpa --no-dev agar phpunit & pint tersedia di dev)
COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-scripts --prefer-dist

COPY . .
COPY docker/app/uploads.ini /usr/local/etc/php/conf.d/99-uploads.ini

USER appuser

EXPOSE 8000

# Eksekusi entrypoint menggunakan sh tanpa bergantung bit executable berkas
ENTRYPOINT ["/bin/sh", "/var/www/html/docker/app/entrypoint.sh"]


