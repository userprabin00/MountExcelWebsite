# Production Deployment Guide

**Mount Excel Boarding School Website**

This document provides step-by-step instructions for deploying this website to a production Linux or cPanel Apache/Nginx web server with PHP 8.0+ and MySQL/MariaDB.

---

## 1. Hosting Requirements

- **Operating System:** Linux (Ubuntu 22.04 LTS / Debian 12 / Rocky Linux) or standard cPanel / DirectAdmin shared/VPS hosting.
- **Web Server:** Apache 2.4+ (with `mod_rewrite` and `mod_headers` enabled) or Nginx with PHP-FPM.
- **PHP Version:** PHP 8.0, 8.1, 8.2, or 8.3.
  - Required Extensions: `pdo_mysql`, `mbstring`, `session`, `openssl`, `json`, `fileinfo`, `curl`.
- **Database:** MySQL 5.7+ / 8.0+ or MariaDB 10.4+.
- **SSL Certificate:** Free Let's Encrypt SSL or commercial certificate (HTTPS is mandatory for admission privacy).

---

## 2. Pre-Deployment Configuration Checklist

### Step 1: Prepare the Files (Exclusions for Deploy Package)
1. Build the production archive with `bash ./build_release.sh` (Linux/macOS) or `./build_release.ps1` (Windows). The scripts exclude the following:
   - `.env` (create on server from `.env.example`)
   - `backups/` contents (the archive retains only a protected empty directory)
   - `tests/`
   - All `*.md` documentation files (except `DEPLOYMENT.md`)
   - `README.md`
   - `database/seed.php` and `database/seed_content.php` (or run CLI once and delete)
   - `.git/`, `.gitignore`, `.vscode/`
   - `logs/` contents (the archive retains only a protected empty directory)
   - Old archive files (`images.zip`, `mounte.zip`, `*.sql` other than `database/schema.sql`)
2. Verify that `.env.example` is present and up-to-date.

The archive omits SQL files other than `database/schema.sql`; apply `database/migrations/001_rate_limits.sql` separately to an existing installation before deploying the new form handlers.

### Step 2: Upload Project to Web Directory
Upload files to your server's public web directory:
- cPanel: `/home/username/public_html/`
- Ubuntu VPS: `/var/www/mountexcel/`

### Step 3: Create Least-Privilege MySQL Database
In cPanel MySQL Database Wizard or via MySQL CLI:
```sql
CREATE DATABASE mount_excel_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'mountexcel_user'@'localhost' IDENTIFIED BY '<replace-with-a-strong-random-password>';
GRANT SELECT, INSERT, UPDATE, DELETE ON mount_excel_prod.* TO 'mountexcel_user'@'localhost';
FLUSH PRIVILEGES;
```
*(Notice: The production application does NOT require DROP or ALTER permissions once migrations are executed).*

### Step 4: Configure Production `.env`
In the webroot on the production server, create `.env` from `.env.example`:
```bash
cp .env.example .env
chmod 600 .env
```
Fill in production values:
```ini
APP_ENV=production
APP_NAME="Mount Excel Boarding School"
APP_KEY="generate_a_random_32_character_string_here"
SITE_URL="https://mountexcel.edu.np"

DB_HOST=localhost
DB_PORT=3306
DB_NAME=mount_excel_prod
DB_USER=mountexcel_user
DB_PASS="<set-a-strong-random-database-password>"

SCHOOL_EMAIL=mountexcel.principal@gmail.com
SCHOOL_PHONE=+977-9851148834
SETUP_TOKEN="<set-a-long-random-setup-token>"
TURNSTILE_SITE_KEY=
TURNSTILE_SECRET=
```

If TLS is terminated by a reverse proxy, put that proxy's trusted IP address(es) in `TRUSTED_PROXIES` as a comma-separated list. Forwarded IP and HTTPS headers are ignored unless the direct peer matches this list.

### Step 5: Run Database Migrations
On the server CLI:
```bash
php database/migrate.php
```
Or import `database/schema.sql` via phpMyAdmin or the mysql CLI:
```bash
mysql -u mountexcel_user -p mount_excel_prod < database/schema.sql
```

### Step 6: Create the Primary School Administrator
1. Set a long random `SETUP_TOKEN` in `.env`. Open your browser and navigate to:
   ```
   https://mountexcel.edu.np/admin/setup.php?token=<your-SETUP_TOKEN>
   ```
2. Enter the official school administrator name, email, and strong password.
3. Once submitted, `setup.php` automatically locks itself against any further registration attempts.

> **⚠️ CRITICAL: Delete `admin/setup.php` immediately after first use.**
> Even though it self-locks, the file should not remain on the server.
> Delete it via SSH or cPanel File Manager:
> ```bash
> rm /home/username/public_html/admin/setup.php
> ```
> Or via cPanel → File Manager → navigate to `admin/` → right-click `setup.php` → Delete.
> Verify it is gone by visiting `https://yourdomain.com/admin/setup.php` — you should receive a **404 Not Found** response.

Create a second super administrator account through the normal user-management screen after setup, and store its credentials securely.

---

## 3. Directory Permissions & File Security

Set strict file permissions on your production server:
```bash
# Directories to 755
find . -type d -exec chmod 755 {} \;

# Files to 644
find . -type f -exec chmod 644 {} \;

# Restrict sensitive files
chmod 600 .env
chmod 750 logs backups database
```

### Apache `.htaccess` Verification
Verify that `.htaccess` is actively read by Apache (`AllowOverride All` in your virtual host config).
Test by visiting `https://yourdomain.com/.env` in a browser. It **must return 403 Forbidden**.

---

## 4. Domain & DNS Configuration

1. In your domain registrar (e.g., Mercantile Communications `.edu.np` portal or registrar):
   - Add **A Record**: `@` &rarr; `<Your Server IP>`
   - Add **CNAME Record**: `www` &rarr; `yourdomain.com`
2. Issue an SSL certificate using Let's Encrypt Certbot:
   ```bash
   sudo certbot --apache -d yourdomain.com -d www.yourdomain.com
   ```
3. Verify that HTTP automatically redirects to HTTPS.

---

## 5. Automated Backup Scheduling (Cron)

Schedule nightly database backups via crontab:
```bash
crontab -e
```
Add the following job to run every night at 2:00 AM:
```cron
0 2 * * * /usr/bin/php /var/www/mountexcel/database/backup.php >> /var/www/mountexcel/logs/cron_backup.log 2>&1
```

Copy each daily `.sql` backup to storage outside this server (for example, encrypted object storage or a separate backup host). A backup kept only on the website server does not protect against server loss.

After configuring an `rclone` remote named `remote` for that off-site storage, add a second scheduled copy:
```cron
15 2 * * * /usr/bin/rclone copy /var/www/mountexcel/backups remote:mount-excel-backups --include '*.sql'
```

## 6. Post-deploy Verification

- Request `/.env` and `/backups/`; both should return **403 Forbidden**.
- Confirm `admin/setup.php` was deleted and returns **404 Not Found**.
- Submit a test admission application and verify its notification email arrives.
- Visit the HTTP site and confirm it redirects to HTTPS.
- Check `/robots.txt` and `/sitemap.xml`; the sitemap URL should serve XML from `sitemap.php`.
- Confirm the public admission and contact forms submit successfully.

---

## 7. Rollback Procedure

If any issue arises during deployment:
1. **File Rollback:** Replace files with the previous verified release archive.
2. **Database Rollback:** Restore the previous database snapshot:
   ```bash
   mysql -u mountexcel_user -p mount_excel_prod < backups/backup_mount_excel_PREVIOUS.sql
   ```
3. Restart Apache/PHP-FPM:
   ```bash
   sudo systemctl restart apache2 php8.2-fpm
   ```
