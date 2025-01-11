# Use PHP with FPM
FROM php:8.1-fpm

# Install necessary PHP extensions and tools
RUN apt-get update && apt-get install -y \
    libzip-dev zip unzip git curl libxml2-dev nginx \
    && docker-php-ext-install pdo pdo_mysql zip dom \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Add Composer global binaries to PATH
ENV PATH="/root/.composer/vendor/bin:$PATH"

# Set the working directory
WORKDIR /var/www/html

# Copy project files into the container
COPY . .

# Create a directory for logs if it doesn't exist
RUN mkdir -p /var/www/html/logs

# Set permissions for the working directory and logs
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/logs

# Copy NGINX configuration file
COPY nginx.conf /etc/nginx/conf.d/default.conf

# Expose port 80 for NGINX
EXPOSE 80

# Command to start PHP-FPM and NGINX
CMD ["sh", "-c", "php-fpm -D && nginx -g 'daemon off;'"]

