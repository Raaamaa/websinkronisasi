# SyncHub Laravel MVP

This is the separate Laravel 12 implementation. The PHP prototype in the parent directory remains intact as a rollback option.

## MVP behavior

- **Super admin:** manages LK definitions and creates/removes admin and employee accounts.
- **Admin:** registers/removes evidence records and selects the target LK.
- **Employee:** searches the catalog by title, document number, or filename; cannot change data.
- Initial LK catalog: internal KIP, SAKIP, ZI, PEKPPP; external Data Sekunder and Data Sektoral.
- Evidence registration hashes the uploaded PDF, DOCX, XLSX, or JPG/PNG with SHA-256. The temporary upload is not moved into application storage. Metadata, the hash, and a manually entered Drive URL are stored in PostgreSQL.
- Search results show a check only for an LK that has a registered record with the exact same SHA-256 hash. Re-saved or re-encoded copies may have a different hash and will not match.
- Google Drive permissions remain controlled by Drive; the app does not connect to its API.
- Local accounts are used until an organizational SSO provider and its configuration are agreed.

## Local Podman setup

From the VS Code PowerShell terminal in the project root:

1. Copy the Laravel environment template:

   ```powershell
   Copy-Item .\laravel\.env.example .\laravel\.env
   ```

2. Set `DB_PASSWORD` and `POSTGRES_PASSWORD` to the same strong local database password in `laravel/.env`. Set `SUPER_ADMIN_USERNAME` and `SUPER_ADMIN_PASSWORD_HASH` to the desired local super-admin username and a PHP password hash. Do not store a plain-text password in `.env`.

3. Install PHP dependencies in the Podman volume used by the app:

   ```powershell
   podman volume create sync-dashboard-laravel-vendor
   podman run --rm -e COMPOSER_PROCESS_TIMEOUT=1800 -v "$($PWD.Path)\laravel:/var/www/html" -v sync-dashboard-laravel-vendor:/var/www/html/vendor -w /var/www/html docker.io/library/composer:2 composer install --no-interaction
   ```

4. Generate the Laravel application key:

   ```powershell
   podman run --rm -v "$($PWD.Path)\laravel:/var/www/html" -v sync-dashboard-laravel-vendor:/var/www/html/vendor -w /var/www/html docker.io/library/composer:2 php artisan key:generate
   ```

5. Start Laravel and PostgreSQL on `127.0.0.1:8082` (kept separate from the prototype on port 8081 and SITIK on port 8080):

   ```powershell
   podman compose --env-file .\laravel\.env -f .\laravel\compose.yaml up -d --build
   podman compose --env-file .\laravel\.env -f .\laravel\compose.yaml exec app php artisan migrate --seed
   ```

6. Open `http://localhost:8082/login`. Seeded super-admin credentials come from `SUPER_ADMIN_USERNAME` and `SUPER_ADMIN_PASSWORD_HASH`.

Stop only the Laravel stack with:

```powershell
podman compose --env-file .\laravel\.env -f .\laravel\compose.yaml down
```

The database persists in the `sync-dashboard-laravel-postgres` Podman volume. `down` does not delete that volume.

## Tests

Tests use SQLite in memory and do not touch the local PostgreSQL volume:

```powershell
podman run --rm -v "$($PWD.Path)\laravel:/var/www/html" -v sync-dashboard-laravel-vendor:/var/www/html/vendor -w /var/www/html docker.io/library/composer:2 php artisan test
```

## Not included yet

SSO, OCR/content-similarity matching, Google Drive API integration, password self-service, audit history, and non-PDF/DOCX/XLSX/JPG/PNG formats are intentionally deferred.
