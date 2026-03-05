## 基础命令

### 生成 SSH 密钥

```` shell
ssh-keygen -t ed25519 -C "your_email@example.com"
cat ~/.ssh/id_ed25519.pub
# 或
cat ~/.ssh/id_rsa.pub
````

### 进入容器（PHP / bash 方式）

```` shell
docker exec -it picgen-php-service bash
````

### 重载配置 & 服务

```` shell
php vendor/bin/envoy run reload
````

````
php artisan telescope:publish
````

### 表结构迁移

```` shell
php artisan migrate
````

````
php artisan migrate:fresh
````

### 填充数据

```` shell
php artisan db:seed
````

### 构建 PHP（Docker） 镜像

````
docker build -t kanekiyuto/php-8.2-cli:latest ./docker/Dockerfile/php
````

````
docker push kanekiyuto/php-8.2-cli:latest
````

````
php artisan waplar:waiter
````

````
php artisan make:migration create_auth_user_table --path=database/migrations/upgrade
````
````
 php artisan vendor:publish --provider=\Illustrator\WaplarServiceProvider.php --tag="waplar-config"
````
````
http://localhost:3011/api/v1/auth/user/login?type=google
````

