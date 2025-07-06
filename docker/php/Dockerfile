FROM php:8.3-cli

RUN apt update \
    && apt install -y \
    libcurl4-openssl-dev \ 
    curl \ 
    ca-certificates \ 
    unzip \
    && update-ca-certificates \
    && docker-php-ext-install curl

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

#Set CA file expicitly
RUN echo "openssl.cafile=/etc/ssl/certs/ca-certificates.crt" >> /usr/local/etc/php/php.ini && \
    echo "curl.cainfo=/etc/ssl/certs/ca-certificates.crt" >> /usr/local/etc/php/php.ini

# Install Composer globally
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /app

CMD ["php", "-a"]