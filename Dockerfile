# Usa PHP 8.2 con Apache para compatibilidad con Filament
FROM php:8.2-apache

# Instala dependencias del sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libicu-dev \
    zip \
    unzip \
    nodejs \
    npm \
    && docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd zip intl

# Instala Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configura Apache
RUN a2enmod rewrite

# Establece el directorio de trabajo
WORKDIR /var/www/html

# Copia archivos del proyecto
COPY . .

# Configura git para permitir el directorio
RUN git config --global --add safe.directory /var/www/html

# Instala dependencias de PHP (ignora requisitos de plataforma si es necesario)
RUN composer install --no-dev --optimize-autoloader --ignore-platform-req=php

# Instala dependencias de Node.js y construye assets
RUN npm install && npm run build

# Configura permisos
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Usa el archivo .env.production del repositorio
RUN mv .env.production .env

# Configura Apache para servir desde la carpeta public de Laravel
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/apache2.conf

# Habilita mod_rewrite para Laravel
RUN a2enmod rewrite

# Crea .htaccess si no existe
RUN echo '<IfModule mod_rewrite.c>\n\
    RewriteEngine On\n\
    RewriteRule ^(.*)$ public/$1 [L]\n\
</IfModule>' > /var/www/html/.htaccess

# Expone el puerto 80
EXPOSE 80

# Comando para iniciar Apache
CMD ["apache2-foreground"]
