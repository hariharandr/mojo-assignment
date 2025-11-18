# 1) Node build
FROM node:18-alpine AS node-builder
WORKDIR /app
COPY package.json package-lock.json ./
COPY . .
ENV NODE_ENV=production
RUN if [ -f package-lock.json ]; then \
      npm ci --legacy-peer-deps; \
    elif [ -f yarn.lock ]; then \
      yarn install --frozen-lockfile; \
    elif [ -f pnpm-lock.yaml ]; then \
      npm i -g pnpm && pnpm install; \
    else \
      npm i --legacy-peer-deps; \
    fi
RUN if [ -f package.json ]; then npm run build || true; fi

# 2) Composer install stage
FROM composer:2 AS composer
WORKDIR /app
COPY composer.json composer.lock /app/
RUN composer install --no-dev --no-interaction --optimize-autoloader --prefer-dist --no-scripts


# 3) Final runtime image (nginx + php-fpm)
FROM richarvey/nginx-php-fpm:3.1.6 AS runtime
ENV WEBROOT=/var/www/html/public
ENV COMPOSER_ALLOW_SUPERUSER=1
WORKDIR /var/www/html

# copy app files (including public build output from node stage)
COPY --chown=www-data:www-data . /var/www/html

# copy composer vendor from composer stage
COPY --from=composer /app/vendor /var/www/html/vendor

# copy built frontend assets from node-builder (if present)
# if Vite outputs to public/build (Laravel default), it will be included by the previous copy.
# Optionally override by explicitly copying from node-builder:
COPY --from=node-builder /app/public /var/www/html/public

# copy nginx config
COPY conf/nginx/nginx-site.conf /etc/nginx/sites-available/default

# ensure writable directories
RUN mkdir -p storage bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache \
 && chmod -R 0775 storage bootstrap/cache

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=3s --start-period=5s \
  CMD wget -qO- --timeout=2 http://localhost/health || exit 1

# the base image's /start.sh will run php-fpm + nginx
CMD ["/start.sh"]
