# Database Backup and Recovery Guide

**Mount Excel Boarding School**

This document describes how to back up and restore the school's MySQL database safely, ensuring zero data loss and compliance with student data privacy guidelines.

---

## 1. Automated Backup Script

The project contains a standalone backup utility at `database/backup.php`. It utilizes `mysqldump` when available, and automatically falls back to native PHP PDO if `mysqldump` is not installed.

### Running a Manual Backup
Open terminal / PowerShell in the project directory:
```bash
php database/backup.php
```

Output:
```
Starting database backup for mount_excel_school...
SUCCESS: Database backup created at: .../backups/backup_mount_excel_YYYY-MM-DD_HH-MM-SS.sql (6.3 KB)
```

### Storage Location & Security Protection
- Backup dumps are placed in the `backups/` directory.
- A strict `.htaccess` file inside `backups/` denies all public web access (`Require all denied`).
- `.gitignore` explicitly prevents database dumps (`*.sql`) from ever being committed to git version control.

---

## 2. Using mysqldump Directly

You can also run standard MySQL client tools directly.

### On Windows (XAMPP):
```powershell
& "C:\xampp\mysql\bin\mysqldump.exe" -u root mount_excel_school > backups\backup_manual.sql
```

### On Linux Production Server:
```bash
mysqldump -u mountexcel_user -p --single-transaction --quick mount_excel_prod > /var/backups/mountexcel_$(date +\%F).sql
```

---

## 3. Database Restoration Procedure

### Restoring on Windows (XAMPP)
1. Verify the MySQL daemon is running.
2. Run the restore command from PowerShell:
   ```powershell
   & "C:\xampp\mysql\bin\mysql.exe" -u root mount_excel_school < backups\backup_mount_excel_2026-10-12_13-55-53.sql
   ```

### Restoring on Linux VPS / cPanel
1. If restoring onto a fresh database:
   ```bash
   mysql -u mountexcel_user -p -e "CREATE DATABASE IF NOT EXISTS mount_excel_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   ```
2. Import the backup SQL file:
   ```bash
   mysql -u mountexcel_user -p mount_excel_prod < backups/backup_mount_excel_2026-10-12_13-55-53.sql
   ```
3. Test restoration by checking table rows:
   ```bash
   php -r "require 'includes/db.php'; \$pdo = getDB(); echo 'Admissions in DB: ' . \$pdo->query('SELECT COUNT(*) FROM admissions')->fetchColumn() . PHP_EOL;"
   ```

### Restoring via phpMyAdmin
1. Log into phpMyAdmin.
2. Select `mount_excel_school` database from the left navigation tree.
3. Click the **Import** tab.
4. Click **Choose File** and select your `.sql` backup file.
5. Ensure character set is set to **utf-8** and click **Import**.

---

## 4. Disaster Recovery & Off-Site Storage

1. **Off-Site Archiving:** Do not leave backups solely on the web server. Copy monthly snapshots to secure, encrypted cold storage (e.g., an encrypted USB drive in the school administrative safe or private cloud storage).
2. **Data Retention:** Retain admission applications for the minimum duration required by education regulatory guidelines, after which inactive rejected applications may be pruned by authorized administrative staff.
