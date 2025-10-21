## srcが無いとき
```
composer create-project --prefer-dist laravel/laravel .
```
でdrive/srcにlaravelプロジェクトを作成
.envがデフォルトでsqliteを使う設定になっているので、mysqlを使う設定に変更

---
```
docker compose build --no-cache  
docker compose up -d
```

## srcが無いとき
```
docker compose exec app composer require laravel/breeze
```
---
docker-compose exec app composer install  
(docker-compose exec app php artisan key:generate)
.envのAPP_KEYがないときに作成するためのコマンド  
docker-compose exec app php artisan migrate

---
## www-dataの権限がないといわれたとき
```
docker compose exec app chown -R www-data:www-data /var/www/html/src/storage
docker compose exec app chown -R www-data:www-data /var/www/html/src/bootstrap/cache
docker compose exec app chmod -R 775 /var/www/html/src/storage
docker compose exec app chmod -R 775 /var/www/html/src/bootstrap/cache
```

## production
composer install
php artisan migrate:fresh

npm ci  
ps aux | grep "node .output/server/index.mjs"  
kill <PID>  
npm run build  
node .output/server/index.mjs

laravelのデフォルトのDBはsqliteなので
DB_CONNECTION=mysql

SESSION_DOMAINも忘れずに変更

## DBへの入り方
mysql -u root -p
