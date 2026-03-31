# Voting System Setup Guide

## 1. XAMPP preparation
1. Start XAMPP Control Panel.
2. Start Apache and MySQL.
3. Confirm Apache on 127.0.0.1:80 and MySQL on 127.0.0.1:3306.

## 2. Ensure database exists
1. Open MySQL Workbench (or phpMyAdmin).
2. Connect with a valid user (default: `root`).
3. If `voting_system` does not exist, create it:
   ```sql
   CREATE DATABASE voting_system;
   USE voting_system;
   SOURCE C:/xampp/htdocs/voting_system/databases/schema.sql;
   ```
4. Verify tables exist:
   ```sql
   SHOW TABLES;
   DESCRIBE students;
   DESCRIBE positions;
   DESCRIBE candidates;
   DESCRIBE votes;
   DESCRIBE integrity;
   DESCRIBE audit_log;
   ```

## 3. db.php connection settings
Edit `backend/db.php` as needed. Default config:
- host: `127.0.0.1`
- port: `3306`
- user: `root`
- pass: empty string (or set to MySQL root password)
- database: `voting_system`

If your root account has a password, either set:
- `DB_PASS` environment variable
- or change the `DB_PASS` value in code

Example fallback logic in `backend/db.php`:
- For `root` with blank password, try blank first.
- For non-root or explicit password, use that directly.

## 4. MySQL Workbench "Access denied" fix
1. In Workbench open connection configuration.
2. Set Dev environment fields:
   - MySQL hostname: `127.0.0.1`
   - Port: `3306`
   - Username: `root` (or service account)
   - Password: leave blank if root no password; set password if root has one.
3. Test connection.

If still denied, run in query tab:
```sql
ALTER USER 'root'@'localhost' IDENTIFIED BY 'newPassword';
FLUSH PRIVILEGES;
```
Then update `backend/db.php` or env accordingly.

## 5. Recommended safer user (non-root)
```sql
CREATE USER 'voterapp'@'localhost' IDENTIFIED BY 'AppPass123';
GRANT ALL PRIVILEGES ON voting_system.* TO 'voterapp'@'localhost';
FLUSH PRIVILEGES;
```
In `backend/db.php` set user `voterapp`, pass `AppPass123`.

## 6. App usage
1. Open browser: `http://127.0.0.1/voting_system/` or `http://localhost/voting_system/`.
2. Use frontend pages:
   - `frontend/admin_login.php` (admin: `admin` / `admin123`)
   - `frontend/register.php` and `frontend/login.php`
   - `frontend/vote.php`
   - `frontend/results.php`

## 7. Troubleshooting
- If white-screen or error, check Apache/PHP logs in XAMPP
- Check `$_SESSION['flash']` messages on UI
- Check Workbench with `SELECT * FROM audit_log ORDER BY log_id DESC LIMIT 10;`
- Ensure `position_id` linkage in all code: `candidates.position_id`, `votes.position_id`.
