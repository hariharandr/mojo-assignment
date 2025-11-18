# Dockerfile (node build + composer + runtime)
# 1) Node build (Debian-based)
FROM node:20-bullseye AS node-builder
WORKDIR /app

# Copy package files first for caching
COPY package.json package-lock.json ./

# Skip optional native deps (avoid @rollup native binary issues) & install
ENV npm_config_optional=false
RUN npm ci --legacy-peer-deps --omit=optional

# Copy project files
COPY . .

# Run Vite build -> generates public/build/manifest.json
RUN npm run build

# 2) Composer install stage
FROM composer:2 AS composer
WORKDIR /app
COPY composer.json composer.lock /app/
RUN composer install --no-dev --no-interaction --optimize-autoloader --prefer-dist --no-scripts

# 3) Final runtime image
FROM richarvey/nginx-php-fpm:3.1.6 AS runtime
ENV WEBROOT=/var/www/html/public
ENV COMPOSER_ALLOW_SUPERUSER=1
WORKDIR /var/www/html

# copy app files (source)
COPY --chown=www-data:www-data . /var/www/html

# copy composer vendor
COPY --from=composer /app/vendor /var/www/html/vendor

# copy built frontend assets (from node-builder)
COPY --from=node-builder /app/public /var/www/html/public

# nginx conf
COPY conf/nginx/nginx-site.conf /etc/nginx/sites-available/default

RUN mkdir -p storage bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache \
 && chmod -R 0775 storage bootstrap/cache

EXPOSE 10000

HEALTHCHECK --interval=30s --timeout=3s --start-period=5s \
  CMD wget -qO- --timeout=2 http://localhost:10000/health || exit 1

CMD ["/start.sh"]
