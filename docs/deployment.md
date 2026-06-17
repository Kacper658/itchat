# Deployment — Ubuntu 24.04 + Apache

## Założenia
Skrypt instalacyjny (patrz `docs/` lub README) instaluje: Apache, PHP 8.3, PostgreSQL, Redis, Node 22, PM2, Composer, Certbot.

## 1. Baza danych PostgreSQL

```bash
sudo -u postgres psql <<'SQL'
CREATE USER itchat WITH PASSWORD 'CHANGE_ME';
CREATE DATABASE itchat OWNER itchat ENCODING 'UTF8';
SQL
```

## 2. Backend (Laravel)

```bash
cd /var/www/backend
composer install --no-dev --optimize-autoloader
cp .env.example .env
# Edytuj .env: DB_*, REDIS_*, DEEPSEEK_API_KEY, P24_*
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force            # seeduje plany + admin
php artisan config:cache
php artisan route:cache
php artisan storage:link
```

### .env (kluczowe)
```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://twojadomena.pl

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_DATABASE=itchat
DB_USERNAME=itchat
DB_PASSWORD=CHANGE_ME

SESSION_DRIVER=redis
CACHE_STORE=redis
QUEUE_CONNECTION=redis

SANCTUM_STATEFUL_DOMAINS=twojadomena.pl
SESSION_DOMAIN=.twojadomena.pl

AI_PROVIDER=deepseek
DEEPSEEK_API_KEY=sk-...
SYSTEM_CURRENCY=PLN

P24_MERCHANT_ID=...
P24_POS_ID=...
P24_CRC_KEY=...
P24_API_KEY=...
P24_ENV=sandbox   # lub live

GEOIP_DB=/usr/share/GeoIP/GeoLite2-Country.mmdb
```

### Queue worker (PM2)
```bash
pm2 start "php /var/www/backend/artisan queue:work redis --sleep=1 --tries=3" --name laravel-queue
pm2 save
```

### PHP-FPM
Apache obsługuje PHP przez `proxy_fcgi` → `php8.3-fpm`. Upewnij się:
```bash
sudo a2enmod proxy_fcgi setenvif
sudo a2enconf php8.3-fpm
sudo systemctl restart php8.3-fpm apache2
```

## 3. Frontend (Next.js)

```bash
cd /var/www/frontend
npm ci
cp .env.example .env.local
# NEXT_PUBLIC_API_URL=https://twojadomena.pl/api
npm run build
pm2 start npm --name nextjs -- start
pm2 save
pm2 startup
```

## 4. Apache VirtualHost

```apache
# /etc/apache2/sites-available/itchat.conf
<VirtualHost *:80>
    ServerName twojadomena.pl
    # Redirect do HTTPS (alternatywa dla Certbota)
    RewriteEngine On
    RewriteRule ^ https://%{SERVER_NAME}%{REQUEST_URI} [L,R=301]
</VirtualHost>

<VirtualHost *:443>
    ServerName twojadomena.pl

    SSLEngine on
    SSLCertificateFile  /etc/letsencrypt/live/twojadomena.pl/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/twojadomena.pl/privkey.pem

    # --- Frontend Next.js ---
    ProxyPreserveHost On
    ProxyPass /api/ !
    ProxyPass / http://localhost:3000/
    ProxyPassReverse / http://localhost:3000/

    # --- API Laravel (PHP-FPM) ---
    DocumentRoot /var/www/backend/public
    <Directory /var/www/backend/public>
        AllowOverride All
        Require all granted
        Options -Indexes
    </Directory>

    # Przekazanie /api/* do PHP-FPM
    <FilesMatch \.php$>
        SetHandler "proxy:unix:/run/php/php8.3-fpm.sock|fcgi://localhost"
    </FilesMatch>
    ProxyTimeout 120
</VirtualHost>
```

```bash
sudo a2ensite itchat
sudo a2dissite 000-default
sudo systemctl reload apache2
sudo certbot --apache -d twojadomena.pl
```

## 5. Firewall

```bash
sudo ufw allow OpenSSH
sudo ufw allow 'Apache Full'
sudo ufw enable
```

## 6. Redis — zabezpieczenie

```bash
# /etc/redis/redis.conf
requirepass CHANGE_ME
```
W `.env`: `REDIS_PASSWORD=CHANGE_ME`

## 7. Cron (Laravel scheduler)

```bash
# crontab -e
* * * * * cd /var/www/backend && php artisan schedule:run >> /dev/null 2>&1
```

Zadania (w `app/Console/Kernel.php` / `routes/console.php`):
- Codziennie: reset `messages_today`, agregacja `daily_stats`.
- Miesięcznie: odnowienie subskrypcji monthly.
```
```bash
# Weryfikacja
sudo -u www-data php artisan migrate:status
curl -k https://localhost/api/geo/detect
```

## 8. Backup PostgreSQL

```bash
# /etc/cron.daily/backup-db
pg_dump -U itchat itchat | gzip > /var/backups/db-$(date +%F).sql.gz
find /var/backups -name "db-*.sql.gz" -mtime +14 -delete
```
