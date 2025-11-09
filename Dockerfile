FROM php:8.4-fpm

# 安装系统依赖和 PHP 扩展（针对 MySQL、zip 等项目可能需要）
RUN apt-get update && apt-get install -y \
    libzip-dev \
    unzip \
    && docker-php-ext-install pdo_mysql zip

# 安装 Composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# 设置工作目录
WORKDIR /var/www/html

# 复制项目文件
COPY . /var/www/html

# 安装 Composer 依赖
RUN composer install --no-dev --optimize-autoloader

# 权限调整（确保 www-data 用户可写日志等）
RUN chown -R www-data:www-data /var/www/html

# 暴露 PHP-FPM 端口
EXPOSE 9000
