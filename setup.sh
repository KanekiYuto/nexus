#!/bin/bash
set -e

echo "===== 初始化 Laravel 应用 ====="

# 将当前系统环境变量写入 .env
# Railway 会将所有 service variables 以 OS 环境变量形式注入，此步骤确保 Laravel 能读取
php -r '
$env = getenv();
$lines = [];
foreach ($env as $key => $value) {
    $escaped = str_replace(["\\", "\""], ["\\\\", "\""], $value);
    $lines[] = $key . "=\"" . $escaped . "\"";
}
file_put_contents("/srv/.env", implode("\n", $lines) . "\n");
echo "  .env 已生成，共 " . count($lines) . " 个变量\n";
'

# 清除旧缓存，重建配置 / 路由 / 视图缓存
echo "===== 构建配置缓存 ====="
php /srv/artisan config:cache
php /srv/artisan route:cache
php /srv/artisan view:cache

# 执行数据库迁移
echo "===== 执行数据库迁移 ====="
php /srv/artisan migrate --force --no-interaction

echo "===== 启动 Supervisor ====="
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
