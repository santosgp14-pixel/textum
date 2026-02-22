FROM php:8.2-apache

# Habilitar mod_rewrite y headers
RUN a2enmod rewrite headers

# Instalar PDO MySQL
RUN docker-php-ext-install pdo pdo_mysql

# Copiar el proyecto
COPY . /var/www/textum

# Configurar VirtualHost apuntando a /public
RUN printf '<VirtualHost *:80>\n\
    DocumentRoot /var/www/textum/public\n\
    <Directory /var/www/textum/public>\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
</VirtualHost>\n' > /etc/apache2/sites-available/000-default.conf

# Permisos
RUN chown -R www-data:www-data /var/www/textum

EXPOSE 80
