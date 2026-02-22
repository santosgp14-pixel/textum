FROM php:8.2-apache

RUN a2enmod rewrite headers

RUN docker-php-ext-install pdo pdo_mysql

COPY . /var/www/textum

RUN chown -R www-data:www-data /var/www/textum

# Al arrancar: Railway inyecta $PORT dinámico, ajustamos Apache en caliente
CMD sed -i "s/Listen 80/Listen ${PORT:-80}/" /etc/apache2/ports.conf \
 && printf '<VirtualHost *:%s>\n  DocumentRoot /var/www/textum/public\n  <Directory /var/www/textum/public>\n    AllowOverride All\n    Require all granted\n  </Directory>\n</VirtualHost>\n' "${PORT:-80}" > /etc/apache2/sites-available/000-default.conf \
 && apache2-foreground
