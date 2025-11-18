# 1) Node build - Use Debian instead of Alpine for better compatibility
FROM node:18-bullseye AS node-builder
WORKDIR /app

# copy package.json first for better caching
COPY package.json package-lock.json ./

# Set environment to skip optional native dependencies
ENV npm_config_optional=false
ENV VITE_SKIP_NATIVE_DEPS=true
ENV NODE_ENV=production

# Install dependencies without optional deps
RUN npm ci --legacy-peer-deps --omit=optional --ignore-scripts

# copy the rest of the project (uses .dockerignore)
COPY . .

# Run build with fallback
RUN npm run build || (echo "Build failed, retrying..." && npm run build)

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

# copy app files
COPY --chown=www-data:www-data . /var/www/html

# copy composer vendor from composer stage
COPY --from=composer /app/vendor /var/www/html/vendor

# copy built frontend assets from node-builder
COPY --from=node-builder /app/public/build /var/www/html/public/build
COPY --from=node-builder /app/public/manifest.json /var/www/html/public/manifest.json 2>/dev/null || true

# copy nginx config
COPY conf/nginx/nginx-site.conf /etc/nginx/sites-available/default

# ensure writable directories
RUN mkdir -p storage bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache \
 && chmod -R 0775 storage bootstrap/cache

EXPOSE 10000

HEALTHCHECK --interval=30s --timeout=3s --start-period=5s \
  CMD wget -qO- --timeout=2 http://localhost:10000/health || exit 1

CMD ["/start.sh"]