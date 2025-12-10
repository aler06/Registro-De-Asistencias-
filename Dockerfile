# Usa Ubuntu como base para mejor compatibilidad con SQL Server
FROM ubuntu:22.04

# Evita prompts interactivos durante la instalación
ENV DEBIAN_FRONTEND=noninteractive

# Instala Apache, PHP y dependencias
RUN apt-get update && apt-get install -y \
    apache2 \
    php8.1 \
    php8.1-cli \
    php8.1-common \
    php8.1-mysql \
    php8.1-xml \
    php8.1-xmlrpc \
    php8.1-curl \
    php8.1-gd \
    php8.1-imagick \
    php8.1-dev \
    php8.1-imap \
    php8.1-mbstring \
    php8.1-opcache \
    php8.1-soap \
    php8.1-zip \
    php8.1-intl \
    php8.1-bcmath \
    git \
    curl \
    zip \
    unzip \
    nodejs \
    npm \
    gnupg \
    software-properties-common \
    && rm -rf /var/lib/apt/lists/*

# Instala Microsoft ODBC Driver y extensiones para SQL Server
RUN curl -fsSL https://packages.microsoft.com/keys/microsoft.asc | gpg --dearmor -o /usr/share/keyrings/microsoft-prod.gpg \
    && curl https://packages.microsoft.com/config/ubuntu/22.04/prod.list | tee /etc/apt/sources.list.d/mssql-release.list \
    && apt-get update \
    && ACCEPT_EULA=Y apt-get install -y msodbcsql18 mssql-tools18 \
    && pecl install sqlsrv pdo_sqlsrv \
    && printf "; priority=20\nextension=sqlsrv.so\n" > /etc/php/8.1/mods-available/sqlsrv.ini \
    && printf "; priority=30\nextension=pdo_sqlsrv.so\n" > /etc/php/8.1/mods-available/pdo_sqlsrv.ini \
    && phpenmod sqlsrv pdo_sqlsrv

# Instala Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Configura Apache
RUN a2enmod rewrite
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Crea directorio para la aplicación
WORKDIR /var/www/html

# Copia archivos del proyecto
COPY . .

# Cambia propiedad de los archivos
RUN chown -R www-data:www-data /var/www/html

# Instala dependencias de PHP
USER www-data
RUN composer install --no-dev --optimize-autoloader

# Instala dependencias de Node.js y construye assets
RUN npm install && npm run build

# Vuelve al usuario root para configurar Apache
USER root

# Configura permisos
RUN chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Usa el archivo .env.production del repositorio
RUN mv .env.production .env

# Configura Apache para usar el directorio público
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

# Expone el puerto 80
EXPOSE 80

# Comando para iniciar Apache
CMD ["apache2ctl", "-D", "FOREGROUND"]
