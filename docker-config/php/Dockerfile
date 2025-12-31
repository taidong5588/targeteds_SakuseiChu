# ============================================
# Stage 1: Composer
# ============================================
FROM composer:latest AS composer-base

# ============================================
# Stage 2: PHP + Node.js 本番環境
# ============================================
FROM php:8.3-fpm

# メタデータ
LABEL maintainer="HubX"
LABEL description="Laravel development environment with PHP 8.3, Node.js and Composer"

# 作業ディレクトリ設定
WORKDIR /var/www

# 環境変数設定
ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_HOME=/tmp/composer \
    NODE_MAJOR=20 \
    DEBIAN_FRONTEND=noninteractive \
    TZ=Asia/Tokyo

# システムパッケージのインストール
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    curl \
    zip \
    unzip \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
    ca-certificates \
    && rm -rf /var/lib/apt/lists/*

# PHP拡張モジュールのインストールと設定
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        intl \
        mbstring \
        pdo \
        pdo_mysql \
        zip \
        bcmath \
        gd \
        exif \
    && docker-php-ext-enable \
        opcache

# Node.jsのインストール（NodeSourceリポジトリから）
RUN curl -fsSL https://deb.nodesource.com/setup_${NODE_MAJOR}.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && npm install -g npm@latest \
    && rm -rf /var/lib/apt/lists/*

# Composerのインストール（Stage 1からコピー）
COPY --from=composer-base /usr/bin/composer /usr/bin/composer

# PHP設定ファイルのコピー
COPY php.ini /usr/local/etc/php/conf.d/custom.ini

# ログディレクトリの作成
RUN mkdir -p /var/log/php \
    && touch /var/log/php/error.log \
    && chmod 666 /var/log/php/error.log

# ユーザーとグループの設定
RUN groupadd -g 1000 www \
    && useradd -u 1000 -g www -m -d /home/www -s /bin/bash www \
    && chown -R www:www /var/www

# 作業ディレクトリの設定と権限
RUN chmod -R 775 /var/www \
    && chown -R www:www /var/www

# Laravelインストーラのグローバルインストール
USER www
RUN composer global require laravel/installer \
    && composer clear-cache

# ポートの公開
EXPOSE 9000

# コンテナ起動時のデフォルトコマンド
CMD ["php-fpm"]
