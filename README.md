# Mount Excel Boarding School

Website and staff administration portal for Mount Excel Boarding School (M.E.B.S.), Indrawati Municipality 12, Bhimtar, Sindhupalchowk, Nepal. The project uses PHP 8, MySQL/MariaDB, Apache, and plain JavaScript/CSS.

## Features

- Responsive public pages for school information, academics, admissions, facilities, staff, gallery, scholarships, excursions, art classes, video, and contact.
- Online admission and contact forms with server-side validation and spam protection.
- Staff portal for admissions, messages, gallery content, staff records, users, settings, and exports.
- PDO database access, session-based authentication, CSRF protection, and environment-based configuration.

## Requirements

- PHP 8.0+ with PDO MySQL, mbstring, sessions, and OpenSSL enabled.
- MySQL 5.7+ or a compatible MariaDB release.
- Apache with `mod_rewrite` and `.htaccess` enabled (needed for clean routes and security rules).

## Local setup (XAMPP)

1. Place or clone the project into `C:\xampp\htdocs\MountExcelWebsite1` and start Apache and MySQL.
2. Copy `.env.example` to `.env`. Set database credentials and use a unique `APP_KEY` and `SETUP_TOKEN`. Keep `.env` private and out of version control.
3. Create the configured database and tables by running `php database/migrate.php` from the project directory. The database account must have permission to create the database if it does not already exist.
4. For first-time admin setup, use the configured token in the setup URL: `http://localhost/MountExcelWebsite1/admin/setup.php?token=YOUR_SETUP_TOKEN`. Setup is unavailable in production when no token is configured, and becomes locked after an administrator is created.
5. Open the public site at `http://localhost/MountExcelWebsite1/` and the staff sign-in at `http://localhost/MountExcelWebsite1/admin/login.php`.

The example environment sets production mode and a public HTTPS site URL. For local development, set `APP_ENV=development` and `SITE_URL=http://localhost/MountExcelWebsite1` in your private `.env`; do not deploy those local values to production. Review `DEPLOYMENT.md` before publishing.

## Project layout

```text
admin/       Staff portal
css/, js/    Public and admin assets
database/    Schema, migrations, and CLI utilities
includes/    Shared configuration, database, security, and page layout code
images/      School media and branding
tests/       Application checks and HTTP test helper
uploads/     Runtime gallery and staff photo uploads
```

## Checks

Run PHP syntax checks with `php -l path/to/file.php`. The scripts under `tests/` include database-backed tests that create records and may create or remove a test database; run them only against a dedicated disposable test database, never the school's live database. The HTTP helper in `tests/test_http.ps1` expects a locally running site.

## Security and contributions

- Never commit `.env`, real credentials, production database dumps, logs, backups, or uploaded private data.
- Use a least-privileged database account in production and rotate secrets before deployment.
- Uploaded image directories are public media locations; the included Apache rules allow approved image formats and block scripts.
- Keep changes focused, preserve the existing school branding and public functionality, and include relevant checks with contributions.

## License

No license is currently included. Add a license file before redistributing or accepting external contributions under an open-source license.
