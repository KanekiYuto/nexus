#!/bin/bash
set -e

echo "===== 初始化 Laravel 应用 ====="

# 诊断：确认 PHP 和 artisan 可正常运行，出错时直接暴露原因
echo "--- PHP 版本 ---"
php -v
echo "--- artisan 检查 ---"
php /srv/artisan --version

echo "===== 执行数据库迁移 ====="
php /srv/artisan migrate --force --no-interaction

echo "===== 启动 Supervisor ====="
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf

cat /srv/storage/logs/laravel.log
