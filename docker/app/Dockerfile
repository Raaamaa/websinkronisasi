FROM php:8.3-cli-alpine

# Install build & runtime dependencies
RUN apk add --no-cache \
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

USER appuser

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]

