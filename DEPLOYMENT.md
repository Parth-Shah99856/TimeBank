# TimeBank — Production Deployment Guide

This guide is written for a first-time deployment of TimeBank on a standard LAMP/LEMP or cPanel/VPS server. Follow the steps in order.

---

## 1. Server Requirements

| Requirement | Minimum | Recommended |
|---|---|---|
| **OS** | Ubuntu 22.04 LTS | Ubuntu 24.04 LTS |
| **Web Server** | Apache 2.4 or Nginx 1.18 | Nginx (latest stable) |
| **PHP** | 8.3 | 8.3 (latest patch) |
| **MySQL** | 8.0 | 8.0+ or MariaDB 10.6+ |
| **Node.js** | 20 LTS | 20 LTS |
| **npm** | 10 | 10+ |
| **Composer** | 2.x | 2.x |
| **Disk space** | 500 MB | 2 GB+ |
| **RAM** | 512 MB | 1 GB+ |

---

## 2. Required PHP Extensions

The following PHP extensions must be enabled:

```
php8.3-cli
php8.3-fpm        (or mod_php for Apache)
php8.3-mysql      (PDO MySQL driver)
php8.3-xml
php8.3-mbstring
php8.3-curl
php8.3-zip
php8.3-bcmath
php8.3-tokenizer
php8.3-ctype
php8.3-fileinfo
php8.3-intl
php8.3-dom
php8.3-openssl
```

Verify installed extensions:

```bash
php -m | grep -E "pdo_mysql|mbstring|xml|curl|zip|bcmath|openssl|intl"
```

---

## 3. MySQL Database Setup

Log in to MySQL as root and create the database and user:

```sql
CREATE DATABASE timebank CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'timebank_user'@'localhost' IDENTIFIED BY 'YOUR_STRONG_PASSWORD';
GRANT ALL PRIVILEGES ON timebank.* TO 'timebank_user'@'localhost';
FLUSH PRIVILEGES;
```

> **Note:** Replace `YOUR_STRONG_PASSWORD` with a strong, randomly generated password.

---

## 4. Application Deployment

### 4.1 Clone / Upload Files

```bash
# Option A — git clone (recommended)
git clone https://github.com/YOUR_ORG/TimeBank.git /var/www/timebank
cd /var/www/timebank

# Option B — upload via SFTP/rsync and extract
```

### 4.2 Set File Permissions

```bash
# Ownership (replace www-data with your web server user if different)
sudo chown -R www-data:www-data /var/www/timebank

# Storage and bootstrap/cache must be writable
sudo chmod -R 775 /var/www/timebank/storage
sudo chmod -R 775 /var/www/timebank/bootstrap/cache
```

### 4.3 Install PHP Dependencies

```bash
composer install --no-dev --optimize-autoloader --no-interaction
```

> **Do not run** `composer install` without `--no-dev` in production. Dev packages (Breeze, Pail, Pint, PHPUnit) are not needed at runtime.

### 4.4 Install Node Dependencies and Build Frontend Assets

```bash
npm ci --ignore-scripts
npm run build
```

This produces the hashed CSS/JS bundles in `public/build/`. The `public/hot` file is gitignored and will not be present in a clean checkout — the app automatically serves from `public/build/` in production.

---

## 5. Environment Configuration

### 5.1 Create the .env File

```bash
cp .env.example .env
```

### 5.2 Edit .env — Required Values

Open `.env` in an editor and fill in every value:

```dotenv
APP_NAME=TimeBank
APP_ENV=production
APP_KEY=                      # generated in step 5.3
APP_DEBUG=false
APP_URL=https://yourdomain.com

LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=timebank
DB_USERNAME=timebank_user
DB_PASSWORD=YOUR_STRONG_PASSWORD

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true    # set false only if not using HTTPS
QUEUE_CONNECTION=database
CACHE_STORE=database

MAIL_MAILER=smtp
MAIL_HOST=your.smtp.host
MAIL_PORT=587
MAIL_USERNAME=your_smtp_user
MAIL_PASSWORD=your_smtp_password
MAIL_FROM_ADDRESS="noreply@yourdomain.com"
MAIL_FROM_NAME="TimeBank"
```

> **Security:** Never commit `.env` to version control. It is in `.gitignore`.

### 5.3 Generate Application Key

```bash
php artisan key:generate
```

This writes a fresh `APP_KEY` into `.env`. This key encrypts sessions and cookies — keep it secret and back it up.

---

## 6. Database Migration

Run all migrations against MySQL:

```bash
php artisan migrate --force
```

> The `--force` flag is required to run migrations in `production` environment without an interactive prompt.

Verify all 16 migrations ran successfully:

```bash
php artisan migrate:status
```

All entries should show `Ran`.

---

## 7. Storage Symbolic Link

The application stores user-uploaded files in `storage/app/public`. Create the symlink so they are publicly accessible:

```bash
php artisan storage:link
```

This creates `public/storage` → `storage/app/public`.

---

## 8. Cache Optimization (Production)

Run all cache commands to optimize performance:

```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views (Blade templates)
php artisan view:cache
```

> **Important:** Any time you change `.env`, run `php artisan config:cache` again. Cached config ignores `.env` changes at runtime.

---

## 9. Web Server Configuration

**The document root must point to `public/`** — not to the project root.

### Apache (.htaccess is already present in public/)

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    DocumentRoot /var/www/timebank/public

    <Directory /var/www/timebank/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/timebank_error.log
    CustomLog ${APACHE_LOG_DIR}/timebank_access.log combined
</VirtualHost>
```

Enable mod_rewrite:

```bash
sudo a2enmod rewrite
sudo systemctl reload apache2
```

### Nginx

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/timebank/public;
    index index.php;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

> **HTTPS:** Configure SSL via Let's Encrypt/Certbot and redirect HTTP to HTTPS. Set `SESSION_SECURE_COOKIE=true` in `.env` when HTTPS is active.

---

## 10. Queue Worker (Recommended)

TimeBank is configured with `QUEUE_CONNECTION=database`. The login alert and OTP emails send synchronously (they do not implement `ShouldQueue`), so basic email delivery works without a worker. However, a worker is recommended so that any future queued jobs (or jobs dispatched by third-party packages) are processed reliably.

**Without a queue worker, jobs dispatched explicitly to the queue will pile up in the `jobs` table unprocessed.**

### Run the worker (foreground, for testing)

```bash
php artisan queue:work --tries=3 --timeout=60
```

### Recommended: Supervisor (keeps worker running permanently)

Install Supervisor:

```bash
sudo apt install supervisor
```

Create `/etc/supervisor/conf.d/timebank-worker.conf`:

```ini
[program:timebank-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/timebank/artisan queue:work --tries=3 --timeout=60 --sleep=3
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/timebank/storage/logs/worker.log
stopwaitsecs=3600
```

Activate:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start timebank-worker:*
```

After each deployment, restart workers:

```bash
sudo supervisorctl restart timebank-worker:*
# or via artisan:
php artisan queue:restart
```

---

## 11. Mail Configuration

TimeBank sends emails for:

| Feature | Notification class |
|---|---|
| Login security alert | LoginAlertNotification |
| Skill Exchange OTP code | SkillExchangeOtpNotification |
| In-app notifications (review, project, etc.) | Various *Notification classes |

All mail goes through the `MAIL_MAILER` defined in `.env`. Set `MAIL_MAILER=smtp` and provide real SMTP credentials. Common providers:

- **SendGrid:** host `smtp.sendgrid.net`, port `587`, username `apikey`, password = your API key
- **Mailgun:** host `smtp.mailgun.org`, port `587`
- **Amazon SES:** use `MAIL_MAILER=ses` (requires AWS credentials)
- **Postmark:** use `MAIL_MAILER=postmark` (requires `POSTMARK_TOKEN`)

> The `MAIL_MAILER=log` setting used during development writes emails to `storage/logs/laravel.log` instead of sending them. Do NOT use `log` in production.

---

## 12. Environment Variables Reference

| Variable | Description | Example |
|---|---|---|
| `APP_KEY` | 32-byte encryption key (generated) | `base64:...` |
| `APP_URL` | Full public URL with scheme | `https://timebank.example.com` |
| `APP_ENV` | Must be `production` | `production` |
| `APP_DEBUG` | Must be `false` | `false` |
| `DB_HOST` | MySQL host | `127.0.0.1` |
| `DB_DATABASE` | Database name | `timebank` |
| `DB_USERNAME` | MySQL user | `timebank_user` |
| `DB_PASSWORD` | MySQL password | *(strong random string)* |
| `SESSION_SECURE_COOKIE` | HTTPS-only cookies | `true` |
| `MAIL_MAILER` | Mail driver | `smtp` |
| `MAIL_HOST` | SMTP host | `smtp.sendgrid.net` |
| `MAIL_PORT` | SMTP port | `587` |
| `MAIL_USERNAME` | SMTP username | `apikey` |
| `MAIL_PASSWORD` | SMTP password / API key | *(your key)* |
| `MAIL_FROM_ADDRESS` | Sender address | `noreply@yourdomain.com` |

---

## 13. Complete Deployment Checklist

```bash
# 1. Clone
git clone https://github.com/YOUR_ORG/TimeBank.git /var/www/timebank
cd /var/www/timebank

# 2. Set permissions
sudo chown -R www-data:www-data .
sudo chmod -R 775 storage bootstrap/cache

# 3. Install PHP deps (no dev)
composer install --no-dev --optimize-autoloader --no-interaction

# 4. Install & build frontend
npm ci --ignore-scripts
npm run build

# 5. Configure environment
cp .env.example .env
nano .env          # fill in DB, MAIL, APP_URL etc.
php artisan key:generate

# 6. Migrate database
php artisan migrate --force

# 7. Storage link
php artisan storage:link

# 8. Cache everything
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 9. Configure web server (point DocumentRoot to public/)
# 10. Start Supervisor queue worker
```

---

## 14. Post-Deployment Verification

```bash
# Health check (should return 200)
curl -s -o /dev/null -w "%{http_code}" https://yourdomain.com/up

# Migration status
php artisan migrate:status

# Route count
php artisan route:list | tail -1   # should say "Showing [73] routes"
```

Manual checks:
- [ ] Home page loads without errors
- [ ] Registration creates user + 5.00 TC signup bonus
- [ ] Login triggers a login alert email to the user
- [ ] Service listing page loads
- [ ] Error pages do NOT show stack traces (APP_DEBUG=false)
- [ ] storage/logs/laravel.log shows error-level entries only
- [ ] Queue worker process is running (supervisorctl status)

---

## 15. Re-Deployment Steps

After each code update:

```bash
git pull origin master
composer install --no-dev --optimize-autoloader
npm ci --ignore-scripts
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
```

---

## 16. Security Notes

- Never expose the project root — web server must serve from `public/` only.
- Never commit `.env` to version control.
- Rotate `APP_KEY` with care — it invalidates all existing encrypted cookies and sessions.
- Use HTTPS in production (`SESSION_SECURE_COOKIE=true`).
- Restrict MySQL user permissions to the `timebank` database only.
- `APP_DEBUG=false` must be set — debug mode can leak stack traces and environment values.
