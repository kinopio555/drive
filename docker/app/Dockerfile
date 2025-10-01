FROM php:8.2-apache

# 作業ディレクトリを設定
WORKDIR /var/www/html

# 必要なパッケージのインストール
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libicu-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl dom xml

# Composerのインストール
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Apache mod_rewriteを有効化
RUN a2enmod rewrite

# Apacheの設定
RUN sed -ri -e 's!/var/www/html!/var/www/html/src/public!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!/var/www/html/src/public!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# PHP設定
RUN cp /usr/local/etc/php/php.ini-development /usr/local/etc/php/php.ini

# ポート80を公開
EXPOSE 80

# Apacheを起動
CMD ["apache2-foreground"]
