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

# Crear el script de inicio DENTRO del container (evita CRLF de Windows)
RUN printf '#!/bin/sh\n\
PORT="${PORT:-80}"\n\
sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf\n\
sed -i "s/<VirtualHost \\*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf\n\
exec apache2-foreground\n' > /start.sh && chmod +x /start.sh

EXPOSE 80

CMD ["/start.sh"]
