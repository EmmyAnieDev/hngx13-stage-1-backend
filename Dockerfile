FROM php:8.3-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpq-dev \
    nginx \
    libonig-dev \
    curl \
    && docker-php-ext-install pdo pdo_pgsql mbstring

# Copy app files
WORKDIR /var/www/html
COPY . .

# Expose PHP-FPM port
EXPOSE 9000

CMD ["php-fpm"]
