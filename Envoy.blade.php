@servers(['localhost' => '127.0.0.1'])

{{-- 重载命令 --}}
@task('reload', ['on' => 'localhost'])
cd /srv
set -e
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan queue:restart
php artisan octane:reload
@endtask
