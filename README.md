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
(docker-compose exec app php artisan key:generate) **二回目以降は禁止かも**  
おそらく.envのAPP_KEYがないときに作成するためのコマンド  
docker-compose exec app php artisan migrate

---
## www-dataの権限がないといわれたとき
```
docker compose exec app chown -R www-data:www-data /var/www/html/src/storage
docker compose exec app chown -R www-data:www-data /var/www/html/src/bootstrap/cache
docker compose exec app chmod -R 775 /var/www/html/src/storage
docker compose exec app chmod -R 775 /var/www/html/src/bootstrap/cache
```

## 学び
XSRF-TOKEN はPOST/PUT/DELETEなどの“状態を変える”リクエストで CSRF を防ぐために使われるもので、GET にはそもそも要求されません。要求されないといっても持っている必要はある。明示的にヘッダーに渡さなくていいだけ
APIトークン
セッションクッキー
laravel auth

## production
npm ci
ps aux | grep "node .output/server/index.mjs"
kill <PID>
npm run build
node .output/server/index.mjs

.envを作成し
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=laravel_db
DB_USERNAME=laravel_user
DB_PASSWORD=laravel_password



SESSION_DOMAINも忘れずに変更