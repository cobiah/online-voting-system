# Voting System Setup Guide

## 1. Current Stack
This project now uses:
- PHP + Apache
- MongoDB via `mongodb/mongodb`
- optional presentation mode for public demo pages

`databases/schema.sql` is a legacy MySQL artifact and is not used by the current runtime.

## 2. Local Requirements
1. Start Apache from XAMPP.
2. Make sure Composer dependencies are installed.
3. Provide a working MongoDB connection string.

Recommended tools:
- XAMPP for Apache
- Composer
- MongoDB Atlas or a local MongoDB server

## 3. Required Environment Variables
Set these before running the app:

Required:
- `MONGODB_URI`
- `MONGODB_DB` default: `voting_system`
- `VOTE_ENCRYPTION_KEY`

Recommended:
- `APP_BASE_PATH=/voting_system` for local XAMPP
- `PRESENTATION_MODE=1` if you want the public pages to keep working with demo data when MongoDB is unavailable
- `DEFAULT_ADMIN_USERNAME`
- `DEFAULT_ADMIN_PASSWORD`
- `DEFAULT_ADMIN_EMAIL`

## 4. Install Dependencies
From the project root run:

```bash
composer install
```

The app expects `vendor/autoload.php` to exist.

## 5. Database Boot Behavior
On the first successful MongoDB connection, the app will:
- seed default departments
- seed default positions
- optionally create a default admin account when `DEFAULT_ADMIN_USERNAME` and `DEFAULT_ADMIN_PASSWORD` are set

There are no built-in hard-coded admin credentials in the current MongoDB flow.

## 6. Local Usage
1. Open `http://127.0.0.1/voting_system/` or `http://localhost/voting_system/`.
2. Use these pages:
   - `frontend/admin_login.php`
   - `frontend/register.php`
   - `frontend/login.php`
   - `frontend/vote.php`
   - `frontend/results.php`

If you want admin login on a fresh setup, define:
- `DEFAULT_ADMIN_USERNAME`
- `DEFAULT_ADMIN_PASSWORD`

## 7. Presentation Mode
Set:

```env
PRESENTATION_MODE=1
```

In presentation mode:
- the homepage can show sample elections
- the public results page can show demo results
- public presentation pages do not hard-fail when MongoDB is unavailable

## 8. Troubleshooting
- If the app fails immediately, check that `vendor/autoload.php` exists.
- If database pages fail, verify `MONGODB_URI` and `MONGODB_DB`.
- Check `backend/health.php` for a quick MongoDB connectivity response.
- Check flash messages in the UI for validation and action errors.

## 9. Render Deployment
This repository is configured for Docker-based deployment on Render.

Files used:
- `Dockerfile`
- `docker/render-entrypoint.sh`
- `render.yaml`

Required Render variables:
- `MONGODB_URI`
- `MONGODB_DB`
- `VOTE_ENCRYPTION_KEY`

Recommended Render variables:
- `APP_BASE_PATH=`
- `PRESENTATION_MODE=1` for demo-friendly public pages
- `DEFAULT_ADMIN_USERNAME`
- `DEFAULT_ADMIN_PASSWORD`
- `DEFAULT_ADMIN_EMAIL`

### Deploy with Blueprint
1. Push the repository to GitHub or GitLab.
2. In Render choose `New` -> `Blueprint`.
3. Select the repository.
4. Fill in the secret environment variables.
5. Deploy.

### Important Upload Note
Candidate images are written to `assets/images/candidates`.
On Render the filesystem is ephemeral by default, so uploads are lost on redeploy unless you mount persistent storage.