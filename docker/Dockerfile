FROM php:8.3-apache

# Dependências do sistema e extensões do PHP
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libldap2-dev \
    libzip-dev \
    zip \
    unzip \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-configure ldap --with-libdir=lib/x86_64-linux-gnu/ \
    && docker-php-ext-install pdo pdo_mysql gd ldap zip \
    && a2enmod rewrite \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Configuração do Apache apontando o DocumentRoot para /public
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Habilita AllowOverride All para o .htaccess funcionar
RUN echo '<Directory /var/www/html/public>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/override.conf \
    && a2enconf override

WORKDIR /var/www/html
