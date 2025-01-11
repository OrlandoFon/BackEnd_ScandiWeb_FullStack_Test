# Use PHP com FPM
FROM php:8.1-fpm

# Instalar extensões PHP e ferramentas necessárias
RUN apt-get update && apt-get install -y \
    libzip-dev zip unzip git curl libxml2-dev nginx \
    && docker-php-ext-install pdo pdo_mysql zip dom \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurar o PATH para Composer global
ENV PATH="/root/.composer/vendor/bin:$PATH"

# Definir o diretório de trabalho
WORKDIR /var/www/html

# Copiar arquivos do projeto para o container
COPY . .

# Criar o diretório de logs, se não existir
RUN mkdir -p /var/www/html/logs

# Ajustar permissões para o diretório de trabalho e logs
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/logs

# Copiar configuração do NGINX
COPY nginx.conf /etc/nginx/conf.d/default.conf

# Expor a porta 80 para o NGINX
EXPOSE 80

# Comando para iniciar PHP-FPM e NGINX
CMD ["sh", "-c", "php-fpm -D && nginx -g 'daemon off;'"]
