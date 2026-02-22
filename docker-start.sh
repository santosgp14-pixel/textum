#!/bin/bash
# Usar el puerto que asigna Railway (o 80 por defecto)
PORT="${PORT:-80}"

# Actualizar el puerto en Apache
sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

echo "Iniciando Apache en puerto ${PORT}..."
exec apache2-foreground
