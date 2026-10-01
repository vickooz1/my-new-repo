FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libcurl4-openssl-dev libonig-dev \
    && docker-php-ext-install -j"$(nproc)" curl mbstring mysqli pdo_mysql \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html
COPY . .

RUN printf 'upload_max_filesize=100M\npost_max_size=105M\nmax_file_uploads=25\nmax_execution_time=120\n' \
        > /usr/local/etc/php/conf.d/uploads.ini \
    && mkdir -p uploads gallery_uploads \
    && chown -R www-data:www-data uploads gallery_uploads

EXPOSE 80