FROM node:22-alpine AS frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY resources ./resources
COPY public ./public
COPY vite.config.js tailwind.config.js postcss.config.js tsconfig.json components.json ./
RUN npm run build

FROM php:8.3-fpm-alpine

RUN apk add --no-cache nginx supervisor curl git unzip mariadb-client libzip-dev \
    libpng-dev libjpeg-turbo-dev freetype-dev libwebp-dev \
    oniguruma-dev libxml2-dev \
    && docker-php-ext-install pdo_mysql mbstring gd xml bcmath zip

COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && chmod -R 775 storage bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

COPY --from=frontend /app/public/build ./public/build

COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
