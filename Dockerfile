# 1) Node build - Use specific Node version with better compatibility
FROM node:18-bullseye AS node-builder
WORKDIR /app

# copy package.json first for better caching
COPY package.json package-lock.json ./

# Set environment to FORCE JavaScript-only Rollup
ENV npm_config_optional=false
ENV VITE_SKIP_NATIVE_DEPS=true
ENV ROLLUP_NATIVE=false
ENV NODE_OPTIONS="--no-wasm --no-experimental-wasm-modules"

# Install dependencies WITHOUT optional dependencies and IGNORE scripts
RUN npm ci --legacy-peer-deps --omit=optional --ignore-scripts

# Force install rollup without native binaries
RUN npm list rollup || npm install rollup@^3.0.0 --no-optional --ignore-scripts

# copy the rest of the project
COPY . .

# Patch rollup to use JavaScript version only
RUN node -e "
const fs = require('fs');
const path = require('path');
const rollupPath = path.join(__dirname, 'node_modules', 'rollup', 'dist', 'native.js');
if (fs.existsSync(rollupPath)) {
  let content = fs.readFileSync(rollupPath, 'utf8');
  // Force rollup to use JavaScript loader instead of native
  content = content.replace(/requireWithFriendlyError\\([^)]*\\)/, 'require(\"./rollup.js\")');
  fs.writeFileSync(rollupPath, content);
  console.log('Patched rollup to use JavaScript version');
}
"

# Run build
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

# copy app files
COPY --chown=www-data:www-data . /var/www/html

# copy composer vendor
COPY --from=composer /app/vendor /var/www/html/vendor

# copy built frontend assets
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