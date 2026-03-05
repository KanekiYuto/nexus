FROM php:8.4-cli

WORKDIR /srv

# 安装必要系统依赖，并清理缓存
RUN apt-get update && \
    DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends \
    net-tools \
    tzdata \
    procps \
    libpq-dev \
    supervisor \
    git \
    unzip \
    wget \
    zlib1g-dev \
    libpng-dev \
    libzip-dev \
    openssh-client \
    && rm -rf /var/lib/apt/lists/*

# 继续其他命令
# 安装 phpredis 扩展
RUN pecl uninstall redis || true
RUN pecl install redis && docker-php-ext-enable redis

# 安装 Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# 设置时区
ENV TZ=Asia/Shanghai
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && \
    echo $TZ > /etc/timezone

# 开启 PHP 源码提取（为安装扩展）
RUN docker-php-source extract

# 安装常用 PHP 扩展
RUN docker-php-ext-install pcntl bcmath pdo_pgsql pgsql sockets gd zip

# 关闭源码提取
RUN docker-php-source delete

# 设置安全目录
RUN git config --global --add safe.directory /srv

# 先复制代码再设置权限（确保目录存在）
COPY . /srv
COPY ./docker/conf/php/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY ./docker/conf/php/php.production.ini /usr/local/etc/php/php.ini

RUN /srv/vendor/bin/rr get-binary

# 设置权限
RUN chown -R www-data:www-data /srv && \
    chmod -R 775 /srv && \
    chmod +x /srv/rr && \
    chmod +x /srv/setup.sh
