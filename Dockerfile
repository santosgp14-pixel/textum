FROM php:8.2-apache

# Habilitar mod_rewrite y mod_headers
RUN a2enmod rewrite headers

# Instalar extensión PDO MySQL
RUN docker-php-ext-install pdo pdo_mysql

# Copiar el proyecto
COPY . /var/www/textum

# Apuntar DocumentRoot a /public
ENV APACHE_DOCUMENT_ROOT /var/www/textum/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf \
 && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/apache2.conf \
    /etc/apache2/conf-available/*.conf

# Permitir .htaccess en el directorio público
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Permisos
RUN chown -R www-data:www-data /var/www/textum

EXPOSE 80
