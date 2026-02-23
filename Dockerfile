FROM php:8.2-cli

RUN docker-php-ext-install pdo pdo_mysql

COPY . /app

WORKDIR /app

# Railway inyecta $PORT en runtime; PHP built-in server no tiene MPM issues
CMD php -S 0.0.0.0:${PORT:-8080} -t public
