#!/bin/bash

# 启动 Supervisor
echo "===== 启动 Supervisor ====="
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
