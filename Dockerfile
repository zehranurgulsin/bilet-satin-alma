FROM php:8.3-apache

# Zaman dilimi (opsiyonel)
ENV TZ=Europe/Istanbul
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# pdo_sqlite için sistem paketi + derleme
RUN apt-get update \
 && apt-get install -y --no-install-recommends libsqlite3-dev \
 && docker-php-ext-configure pdo_sqlite --with-pdo-sqlite=/usr \
 && docker-php-ext-install -j"$(nproc)" pdo_sqlite \
 && rm -rf /var/lib/apt/lists/*

# (opsiyonel) performans: opcache
RUN docker-php-ext-install opcache \
 && { \
    echo 'opcache.enable=1'; \
    echo 'opcache.validate_timestamps=0'; \
    echo 'opcache.memory_consumption=256'; \
    echo 'opcache.jit_buffer_size=128M'; \
 } > /usr/local/etc/php/conf.d/opcache.ini

# Apache rewrite (htaccess kullanıyorsan)
RUN a2enmod rewrite

# Uygulama dosyaları
COPY . /var/www/html

# SQLite dosyası için yazma izni
RUN mkdir -p /var/www/html/data \
 && chown -R www-data:www-data /var/www/html

EXPOSE 80
