# Security Controls & Compliance Checklist

**Mount Excel Boarding School Website**  
**Standards:** OWASP Top 10 Web Application Security, Child Data Privacy Best Practices

---

## 1. Implemented Security Controls

### A. Authentication & Session Management
- [x] **Password Hashing:** Passwords hashed using PHP's native `password_hash()` (Bcrypt/Argon2 with work factor cost).
- [x] **Session Fixation Defense:** `session_regenerate_id(true)` called immediately upon authentication.
- [x] **Secure Session Cookies:** `HttpOnly = true`, `SameSite = Lax`, `use_strict_mode = 1`, and `Secure = true` automatically set over HTTPS.
- [x] **Session Inactivity Timeout:** Automatic logout after 2 hours (7200 seconds) of inactivity.
- [x] **Brute-Force Protection:** Account locking after 5 failed login attempts for 15 minutes, with IP-level rate limiting.
- [x] **Zero Hardcoded Secrets:** No default passwords or sensitive credentials hardcoded in source files.
- [x] **Secure Setup Lock:** Initial admin setup script (`admin/setup.php`) auto-locks as soon as an administrator exists.

### B. Input Validation & Data Sanitation
- [x] **SQL Injection Defense:** Zero dynamic string concatenation in SQL queries. 100% PDO prepared statements with parameterized inputs.
- [x] **Cross-Site Scripting (XSS) Defense:** All user-supplied output passed through contextual escaping `e()` (`htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`).
- [x] **Cross-Site Request Forgery (CSRF):** Unique cryptographically secure session tokens (`random_bytes(32)`) embedded in all public and admin forms and strictly verified.
- [x] **Anti-Spam Honeypots:** Invisible honeypot traps on both admission and contact forms to catch automated bot scrapers.
- [x] **Unicode Name Support:** Regex validator properly supports Nepali Devanagari Unicode characters without permitting malicious punctuation or script injection.
- [x] **Phone Validation:** Restricts input to legitimate Nepali mobile and landline patterns.

### C. Server & Network Hardening
- [x] **Directory Indexing Disabled:** `Options -Indexes` enforced in `.htaccess`.
- [x] **Direct File Access Block:** `.htaccess` blocks public web access to `.env`, `.sql`, `.log`, `.bak`, `.zip`, and `.tar` files.
- [x] **Security Headers Configured:**
  - `X-Frame-Options: SAMEORIGIN` (Clickjacking defense)
  - `X-Content-Type-Options: nosniff` (MIME sniffing defense)
  - `X-XSS-Protection: 1; mode=block`
  - `Referrer-Policy: strict-origin-when-cross-origin`
  - `Permissions-Policy: geolocation=(), camera=(), microphone=()`
- [x] **Error Leakage Prevention:** In `production` mode (`APP_ENV=production`), `display_errors` is disabled and detailed errors are routed to protected `logs/app_error.log`.

### D. Student & Family Privacy
- [x] **Confidentiality:** Admission applications are strictly walled behind the authenticated `/admin/` portal.
- [x] **Formula Injection Defense:** Safe CSV export prefixes cells with single quotes if starting with dangerous spreadsheet formula triggers (`=`, `+`, `-`, `@`).
- [x] **Data Minimization:** Only collecting student biodata, guardian contact, and grade information necessary for school enrollment.

---

## 2. Deployment Recommendations & Outstanding Operational Risks

| Item | Recommendation | Priority |
|------|----------------|----------|
| **HTTPS Certificate** | Production hosting must enforce TLS 1.3/1.2 with an SSL certificate. | **HIGH** |
| **Database User Permissions** | Production MySQL user should only have `SELECT, INSERT, UPDATE, DELETE` permissions. | **HIGH** |
| **Server Backups** | Implement off-site backup storage schedule as documented in `BACKUP_AND_RESTORE.md`. | **MEDIUM** |
| **SMTP Delivery** | When the school is ready for email notifications, configure a dedicated authenticated SMTP service (e.g. Google Workspace SMTP or SendGrid). | **LOW** |
