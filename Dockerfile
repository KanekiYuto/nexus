FROM php:8.4-cli

# 创建工作目录结构
WORKDIR /srv

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
