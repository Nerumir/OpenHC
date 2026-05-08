FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    libpng-dev \
    libxml2-dev \
    oniguruma-dev \
    icu-dev \
    zip \
    unzip \
    mysql-client \
    nodejs \
    npm \
    chromium \
    chromium-swiftshader \
    ttf-freefont \
    font-noto-emoji

ENV PUPPETEER_SKIP_DOWNLOAD=true \
    PUPPETEER_EXECUTABLE_PATH=/usr/bin/chromium-browser \
    CHROME_PATH=/usr/bin/chromium-browser

RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    intl

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Install PHP deps (--no-scripts : artisan n'est pas encore disponible)
COPY app/composer.json app/composer.lock ./
RUN composer install --no-dev --no-scripts --no-interaction --no-progress

# Install JS deps (layer cached until package.json/lock changes)
COPY app/package.json app/package-lock.json ./
RUN npm ci

# Copy app, finaliser l'autoloader et builder les assets
COPY app/ .
RUN echo "APP_KEY=base64:$(openssl rand -base64 32)" > .env \
    && echo "APP_NAME=OpenHC" >> .env \
    && echo "VITE_APP_NAME=OpenHC" >> .env \
    && composer dump-autoload --optimize \
    && npm run build \
    && rm -f .env

COPY nginx/default.conf /etc/nginx/http.d/default.conf
COPY supervisord.conf /etc/supervisord.conf
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
