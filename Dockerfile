# Use the official PHP 8.1 FPM image
FROM php:8.1-fpm

# Install necessary PHP extensions
RUN apt-get update && apt-get install -y \
    libzip-dev zip unzip git curl libxml2-dev && \
    docker-php-ext-install pdo pdo_mysql zip dom && \
    pecl install xdebug && \
    docker-php-ext-enable xdebug

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set the working directory
WORKDIR /var/www/html

# Copy the project files to the container
COPY . /var/www/html

# Install project dependencies using Composer
RUN composer install --no-dev --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Expose the PHP server on port 9000
EXPOSE 9000

# Command to start PHP-FPM
CMD ["php-fpm"]


