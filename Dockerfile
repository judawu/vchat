# Stage 1: use composer image to install dependencies
FROM composer:2 AS vendor

WORKDIR /app
# 先复制 composer.json/composer.lock 以利用缓存
COPY composer.json composer.lock* /app/
# 使用 --no-dev 并优化 autoloader；如果需要 dev 依赖去掉 --no-dev
RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader

# Stage 2: php-fpm image
FROM php:8.4-fpm

# 安装系统依赖和 PHP 扩展
RUN apt-get update && apt-get install -y --no-install-recommends \
    libzip-dev \
    libpq-dev \
    git \
    nano \
    curl \
    unzip \
    && docker-php-ext-install pdo_mysql pdo_pgsql zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# 把 composer 可执行文件拷贝进来（可选，php:*-fpm 通常没有 composer）
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/html

# 复制应用代码（先复制除了 vendor 的文件）
COPY . /var/www/html

# 从 vendor 阶段拷贝已安装的依赖
COPY --from=vendor /app/vendor /var/www/html/vendor
COPY --from=vendor /app/vendor /usr/local/lib/php/vendor

# 修复可能的 Git Dubious Ownership 问题（如果需要）
RUN git config --global --add safe.directory /var/www/html || true

# 创建日志目录并设置权限
RUN mkdir -p /var/www/html/logs \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 750 /var/www/html/logs

# 将 php-fpm 监听地址改为 0.0.0.0:9000（如果需要）
RUN sed -i 's/^listen = .*/listen = 0.0.0.0:9000/' /usr/local/etc/php-fpm.d/www.conf

EXPOSE 9000

# 以 php-fpm 作为默认命令（镜像基础已提供）
CMD ["php-fpm"]
