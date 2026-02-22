FROM php:8.2-apache

# Desactivar MPMs extras, dejar solo prefork (requerido por mod_php)
# Luego habilitar rewrite y headers
RUN a2dismod mpm_event mpm_worker 2>/dev/null; a2enmod mpm_prefork rewrite headers

RUN docker-php-ext-install pdo pdo_mysql

COPY . /var/www/textum

RUN chown -R www-data:www-data /var/www/textum

# Al arrancar: Railway inyecta $PORT dinámico, ajustamos Apache en caliente
CMD sed -i "s/Listen 80/Listen ${PORT:-80}/" /etc/apache2/ports.conf \
 && printf '<VirtualHost *:%s>\n  DocumentRoot /var/www/textum/public\n  <Directory /var/www/textum/public>\n    AllowOverride All\n    Require all granted\n  </Directory>\n</VirtualHost>\n' "${PORT:-80}" > /etc/apache2/sites-available/000-default.conf \
 && apache2-foreground
