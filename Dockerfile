# 1) Node build stage (Vite / Tailwind build)
FROM node:18-alpine AS node-builder
WORKDIR /app

# install build deps (copy only package.json first, then any lockfile if present)
COPY package.json ./

# copy lockfile(s) if present (safe - will not error if missing)
# Note: Dockerfile COPY won't ignore missing files, so copy each individually
# and rely on the conditional installer below.
COPY package-lock.json ./ 2>/dev/null || true
COPY yarn.lock ./ 2>/dev/null || true
COPY pnpm-lock.yaml ./ 2>/dev/null || true

RUN if [ -f package-lock.json ]; then npm ci --legacy-peer-deps; \
    elif [ -f yarn.lock ]; then yarn install --frozen-lockfile; \
    elif [ -f pnpm-lock.yaml ]; then npm i -g pnpm && pnpm install; \
    else echo "No lockfile found, running npm install"; npm i --legacy-peer-deps; fi


# copy frontend & build
COPY . .
# ensure VITE runs in production
ENV NODE_ENV=production
RUN if [ -f package.json ]; then npm run build || true; fi

# 2) Composer install stage
FROM composer:2 AS composer
WORKDIR /app
COPY composer.json composer.lock /app/
RUN composer install --no-dev --no-interaction --optimize-autoloader --prefer-dist --no-scripts
# copy vendor to a holder (composer stage)
WORKDIR /app
# (vendor exists in /app/vendor)

# 3) Final runtime image (nginx + php-fpm)
FROM richarvey/nginx-php-fpm:3.1.6 AS runtime
ENV WEBROOT=/var/www/html/public
ENV COMPOSER_ALLOW_SUPERUSER=1
WORKDIR /var/www/html

# copy php app files
COPY --chown=www-data:www-data . /var/www/html

# copy composer vendor from composer stage
COPY --from=composer /app/vendor /var/www/html/vendor
COPY --from=composer /app/vendor /var/www/html/vendor

# copy built frontend assets from node-builder (defaults to public/build)
# adjust path if your Vite config outputs elsewhere
COPY --from=node-builder /app/public /var/www/html/public
# if your build output is in /app/resources/dist or similar, copy that too (adjust as needed)

# copy nginx conf
COPY conf/nginx/nginx-site.conf /etc/nginx/sites-available/default

# ensure writable directories
RUN mkdir -p storage bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache \
 && chmod -R 0775 storage bootstrap/cache

EXPOSE 10000

HEALTHCHECK --interval=30s --timeout=3s --start-period=5s \
  CMD wget -qO- --timeout=2 http://localhost:10000/health || exit 1

# the base image uses /start.sh to run php-fpm + nginx
CMD ["/start.sh"]
