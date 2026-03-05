#!/bin/bash
set -e

echo "===== 初始化 Laravel 应用 ====="

# 执行数据库迁移
# echo "===== 执行数据库迁移 ====="
# php /srv/artisan migrate

echo "===== 启动 Supervisor ====="
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
