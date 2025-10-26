# Base image
FROM php:8.2-apache

LABEL maintainer="jekkos / modified for Render deployment"

# Install dependencies
RUN apt-get update && apt-get install -y \
    libicu-dev libgd-dev libzip-dev unzip git wget curl \
    && docker-php-ext-install mysqli bcmath intl gd zip

# Enable Apache rewrite
RUN a2enmod rewrite

# Set timezone
ENV PHP_TIMEZONE=Asia/Jakarta
RUN echo "date.timezone = \"${PHP_TIMEZONE}\"" > /usr/local/etc/php/conf.d/timezone.ini

# Copy project files
WORKDIR /app
COPY . /app

# Install Composer (✅ ini bagian penting)
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Jalankan Composer install (✅ ini juga penting)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Link public folder ke Apache root
RUN rm -rf /var/www/html && ln -s /app/public /var/www/html

# Permissions
RUN mkdir -p /app/writable/cache /app/writable/logs /app/writable/uploads && \
    chmod -R 770 /app/writable && \
    chown -R www-data:www-data /app

# Expose port 8080 (Render uses this)
EXPOSE 8080
ENV PORT 8080

# Jalankan Apache
CMD ["apache2-foreground"]
