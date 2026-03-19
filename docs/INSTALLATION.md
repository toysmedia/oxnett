# OxNet ISP SaaS Platform — Installation Guide

> **Platform:** OxNet Multi-Tenant ISP Management SaaS  
> **Stack:** Laravel 10 · PHP 8.1+ · MySQL 8 · Node.js 16+

---

## Table of Contents

1. [Prerequisites](#1-prerequisites)
2. [Local Development Setup](#2-local-development-setup)
3. [Production / Live Server Deployment](#3-production--live-server-deployment)
4. [Environment Variables Reference](#4-environment-variables-reference)
5. [First-Run Verification Checklist](#5-first-run-verification-checklist)

---

## 1. Prerequisites

### Local Development
| Requirement | Minimum Version | Notes |
|-------------|----------------|-------|
| PHP | 8.1 | Extensions: `pdo_mysql`, `pdo_sqlite`, `mbstring`, `xml`, `curl`, `gd`, `zip`, `bcmath` |
| Composer | 2.x | `composer --version` |
| Node.js | 16+ | `node --version` |
| MySQL / MariaDB | 5.7+ / 10.3+ | Or SQLite for quick local dev |
| Git | Any | |

### Production Server
| Requirement | Notes |
|-------------|-------|
| Ubuntu 22.04 LTS | Recommended; CentOS 8+ also supported |
| Nginx or Apache | Nginx recommended for wildcard subdomains |
| PHP-FPM 8.1+ | With the same extensions as above |
| MySQL 8.0 | One system DB + per-tenant databases |
| SSL Certificate | Let's Encrypt wildcard (`*.oxnet.co.ke`) |
| Supervisor | For queue workers |

---

## 2. Local Development Setup

### Step 1 — Clone the Repository

```bash
git clone https://github.com/toysmedia/oxnett.git
cd oxnett
```

### Step 2 — Install PHP Dependencies

```bash
composer install
```

### Step 3 — Install & Build Frontend Assets

```bash
npm install
npm run build
# For hot-reload during development:
npm run dev
```

### Step 4 — Configure Environment

```bash
cp .env.example .env
php artisan key:generate
```

Open `.env` and fill in the required values (see [Section 4](#4-environment-variables-reference) for details):

```env
APP_NAME="OxNet ISP Platform"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=oxnet_system
DB_USERNAME=root
DB_PASSWORD=your_password
```

### Step 5 — Create the System Database

```sql
-- In MySQL:
CREATE DATABASE oxnet_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### Step 6 — Run Migrations & Seed Data

```bash
# System-level migrations (pricing plans, tenants, super admin users, community, AI)
php artisan migrate

# Seed default data (super admin, pricing plans, CMS defaults, community categories)
php artisan db:seed

# Optional: seed test credentials for all portals
php artisan db:seed --class=TestDataSeeder
```

### Step 7 — Configure Local Subdomain Routing

Since OxNet uses **subdomain-per-tenant**, you need wildcard subdomain support locally.

**Option A — Laravel Valet (macOS)**

```bash
valet park  # inside the oxnett directory
valet link oxnett
# Access: oxnett.test  |  demo.oxnett.test
```

**Option B — /etc/hosts (any OS)**

Add to `/etc/hosts`:

```
127.0.0.1   oxnett.test
127.0.0.1   demo.oxnett.test
```

Then set `APP_URL=http://oxnett.test` in `.env`.

**Option C — php artisan serve (no subdomains)**

```bash
php artisan serve
```

> ⚠️  `php artisan serve` does **not** support subdomains. Admin, Customer, and Seller portals will work at `localhost:8000/admin`, `localhost:8000/customer`, etc., but the multi-tenant resolver will not be active.

### Step 8 — Start the Development Server

```bash
php artisan serve
# Visit: http://localhost:8000
```

### Step 9 — Create a Storage Symlink

```bash
php artisan storage:link
```

### Step 10 — Quick Login URLs

After seeding with `TestDataSeeder`:

| Portal | URL | Email/Username | Password |
|--------|-----|----------------|----------|
| Super Admin | `/super-admin/login` | `superadmin@oxnet.co.ke` | `password` |
| Admin (Tenant) | `demo.{host}/admin/login` | `admin@demo.oxnet.co.ke` | `password` |
| PPPoE Customer | `demo.{host}/customer/login` | `testcustomer` | `password` |
| Community | `/community/login` | `member@community.test` | `password` |

---

## 3. Production / Live Server Deployment

### 3.1 Server Setup (Ubuntu 22.04)

```bash
# Update packages
sudo apt update && sudo apt upgrade -y

# Install Nginx, PHP 8.2-FPM, MySQL, Node.js, Composer
sudo apt install -y nginx mysql-server php8.2-fpm php8.2-cli php8.2-mysql \
  php8.2-mbstring php8.2-xml php8.2-curl php8.2-gd php8.2-zip \
  php8.2-bcmath php8.2-tokenizer unzip git curl supervisor

# Install Node.js 18 (LTS)
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs

# Install Composer globally
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### 3.2 Configure MySQL

```bash
sudo mysql_secure_installation
sudo mysql -u root -p

# In MySQL:
CREATE DATABASE oxnet_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'oxnet_user'@'localhost' IDENTIFIED BY 'StrongPassword!';
GRANT ALL PRIVILEGES ON `oxnet_%`.* TO 'oxnet_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

> The `oxnet_%` wildcard grant allows the system to automatically create per-tenant databases.

### 3.3 Clone & Configure the Application

```bash
cd /var/www
sudo git clone https://github.com/toysmedia/oxnett.git oxnett
sudo chown -R www-data:www-data /var/www/oxnett

cd /var/www/oxnett
sudo -u www-data composer install --no-dev --optimize-autoloader
sudo -u www-data npm install && sudo -u www-data npm run build

cp .env.example .env
php artisan key:generate

# Set storage permissions
sudo chmod -R 775 storage bootstrap/cache
sudo chown -R www-data:www-data storage bootstrap/cache
php artisan storage:link
```

### 3.4 Configure .env for Production

```env
APP_NAME="OxNet ISP Platform"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://oxnet.co.ke

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=oxnet_system
DB_USERNAME=oxnet_user
DB_PASSWORD=StrongPassword!

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=127.0.0.1
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=your-mailgun-user
MAIL_PASSWORD=your-mailgun-password
MAIL_FROM_ADDRESS=noreply@oxnet.co.ke

# M-Pesa Daraja API
MPESA_ENV=production
MPESA_CONSUMER_KEY=...
MPESA_CONSUMER_SECRET=...
MPESA_PASSKEY=...
MPESA_SHORTCODE=...
MPESA_TILL_NUMBER=...

# OpenAI (AI Assistant)
OPENAI_API_KEY=sk-...
OPENAI_MODEL=gpt-4o-mini
```

### 3.5 Run Migrations & Seed

```bash
php artisan migrate --force
php artisan db:seed --force
```

### 3.6 Nginx — Wildcard Subdomain Virtual Host

Create `/etc/nginx/sites-available/oxnet`:

```nginx
# Redirect HTTP → HTTPS
server {
    listen 80;
    server_name oxnet.co.ke *.oxnet.co.ke;
    return 301 https://$host$request_uri;
}

# Main HTTPS server — handles oxnet.co.ke AND all *.oxnet.co.ke subdomains
server {
    listen 443 ssl http2;
    server_name oxnet.co.ke *.oxnet.co.ke;

    root /var/www/oxnett/public;
    index index.php;

    # SSL — Let's Encrypt wildcard certificate
    ssl_certificate     /etc/letsencrypt/live/oxnet.co.ke/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/oxnet.co.ke/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    # Laravel routing
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    }

    location ~ /\.ht {
        deny all;
    }

    # Static assets — long cache
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff2?)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/oxnet /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### 3.7 SSL Certificate — Let's Encrypt Wildcard

```bash
sudo apt install certbot python3-certbot-nginx

# Wildcard cert requires DNS challenge (add TXT record to your DNS)
sudo certbot certonly --manual --preferred-challenges dns \
  -d oxnet.co.ke -d "*.oxnet.co.ke"

# Auto-renew cron
echo "0 3 * * * root certbot renew --quiet --post-hook 'systemctl reload nginx'" \
  | sudo tee /etc/cron.d/certbot-renew
```

### 3.8 DNS Configuration

Add the following DNS records for your domain:

| Type | Name | Value | TTL |
|------|------|-------|-----|
| A | `@` | `YOUR_SERVER_IP` | 300 |
| A | `*` | `YOUR_SERVER_IP` | 300 |
| CNAME | `www` | `oxnet.co.ke` | 300 |

### 3.9 Queue Workers — Supervisor

Create `/etc/supervisor/conf.d/oxnet-worker.conf`:

```ini
[program:oxnet-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/oxnett/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/oxnet-worker.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start oxnet-worker:*
```

### 3.10 Scheduler (Subscription Checks, Expiry Notifications)

Add to the server's crontab (`sudo crontab -e -u www-data`):

```cron
* * * * * cd /var/www/oxnett && php artisan schedule:run >> /dev/null 2>&1
```

### 3.11 Security Hardening Checklist

- [ ] `APP_DEBUG=false` in `.env`
- [ ] `APP_ENV=production` in `.env`
- [ ] Strong, unique `APP_KEY` generated with `php artisan key:generate`
- [ ] MySQL user has **only** the required privileges (no SUPER)
- [ ] Nginx configured with `server_tokens off;`
- [ ] HTTPS enforced; HTTP redirects to HTTPS
- [ ] Wildcard SSL certificate from Let's Encrypt
- [ ] `storage/` and `bootstrap/cache/` owned by `www-data`, not world-writable
- [ ] `.env` not readable by others: `chmod 640 .env`
- [ ] Firewall: only ports 22, 80, 443 open (`ufw allow OpenSSH && ufw allow 'Nginx Full' && ufw enable`)
- [ ] M-Pesa IP whitelist enforced via `VerifyMpesaIp` middleware
- [ ] CORS configured in `config/cors.php` to allow only your domains
- [ ] `php artisan config:cache && php artisan route:cache && php artisan view:cache` for performance

### 3.12 Post-Deployment Verification

```bash
# Verify routes are cached correctly
php artisan route:list | grep super-admin

# Verify no DB errors
php artisan migrate:status

# Check queue worker
sudo supervisorctl status oxnet-worker:*

# Send a test email
php artisan tinker --execute="Mail::raw('Test', fn(\$m) => \$m->to('you@example.com')->subject('OxNet Test'));"
```

---

## 4. Environment Variables Reference

| Variable | Description | Example |
|----------|-------------|---------|
| `APP_NAME` | Application name shown in emails/UI | `OxNet ISP Platform` |
| `APP_ENV` | `local` or `production` | `production` |
| `APP_DEBUG` | Show stack traces (never true in production) | `false` |
| `APP_URL` | Base URL of the application | `https://oxnet.co.ke` |
| `DB_DATABASE` | System database name | `oxnet_system` |
| `DB_USERNAME` | MySQL username with `oxnet_%` privileges | `oxnet_user` |
| `MPESA_CONSUMER_KEY` | Safaricom Daraja consumer key | (from Daraja portal) |
| `MPESA_SHORTCODE` | Safaricom paybill/till number | `174379` |
| `OPENAI_API_KEY` | OpenAI API key for AI Assistant | `sk-...` |
| `QUEUE_CONNECTION` | `sync` for dev, `redis` for production | `redis` |

---

## 5. First-Run Verification Checklist

After installation, verify the following URLs respond correctly:

| URL | Expected Result |
|-----|----------------|
| `/` | Guest home page loads |
| `/super-admin/login` | Super Admin login form |
| `/super-admin/dashboard` | Redirects to login (unauthenticated) |
| `/admin/login` | Admin tenant login form |
| `{subdomain}/customer/login` | Customer portal login |
| `/community/login` | Community login |
| `/seller/login` | Seller/worker login |
| `/api/health` | `{"status":"ok"}` or 200 |
