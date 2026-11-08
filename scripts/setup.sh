#!/usr/bin/env bash
set -euo pipefail

echo "=== Setup Lingkungan Websinkronisasi ==="

if [ ! -f ".env" ]; then
    cp .env.example .env
    echo "Berkas .env berhasil disalin dari .env.example."
fi

# Helper untuk memperbarui/menambahkan variabel ke .env secara aman tanpa sed rapuh
set_env_key() {
    local key="$1"
    local val="$2"
    local file="${3:-.env}"
    [ -f "$file" ] || touch "$file"

    if command -v php >/dev/null 2>&1; then
        TARGET_FILE="$file" ENV_KEY="$key" ENV_VAL="$val" php <<'PHP'
<?php
$file = getenv('TARGET_FILE');
$key  = getenv('ENV_KEY');
$val  = getenv('ENV_VAL');

$escaped = addcslashes($val, "\\$\"");
$line = $key . '="' . $escaped . '"';

$content = file_exists($file) ? file_get_contents($file) : '';
$pattern = '/^' . preg_quote($key, '/') . '=.*/m';

if (preg_match($pattern, $content)) {
    $newContent = preg_replace($pattern, $line, $content);
} else {
    $newContent = rtrim($content, "\r\n");
    if ($newContent !== '') {
        $newContent .= "\n";
    }
    $newContent .= $line . "\n";
}
file_put_contents($file, $newContent);
PHP
    elif command -v python3 >/dev/null 2>&1; then
        TARGET_FILE="$file" ENV_KEY="$key" ENV_VAL="$val" python3 <<'PY'
import os, re
file_path = os.environ.get("TARGET_FILE", ".env")
key = os.environ.get("ENV_KEY", "")
val = os.environ.get("ENV_VAL", "")
escaped = val.replace("\\", "\\\\").replace('"', '\\"').replace("$", "\\$")
line = f'{key}="{escaped}"'
content = ""
if os.path.exists(file_path):
    with open(file_path, "r", encoding="utf-8") as f:
        content = f.read()
pattern = rf"^{re.escape(key)}=.*$"
if re.search(pattern, content, flags=re.MULTILINE):
    new_content = re.sub(pattern, line, content, flags=re.MULTILINE)
else:
    new_content = content.rstrip("\r\n")
    if new_content:
        new_content += "\n"
    new_content += line + "\n"
with open(file_path, "w", encoding="utf-8") as f:
    f.write(new_content)
PY
    fi
}

if [ -z "${DB_PASSWORD:-}" ]; then
    read -rsp "Masukkan DB_PASSWORD yang aman: " DB_PASSWORD
    echo
    export DB_PASSWORD
fi
set_env_key "DB_PASSWORD" "$DB_PASSWORD"

if [ -z "${REDIS_PASSWORD:-}" ]; then
    read -rsp "Masukkan REDIS_PASSWORD yang aman: " REDIS_PASSWORD
    echo
    export REDIS_PASSWORD
fi
set_env_key "REDIS_PASSWORD" "$REDIS_PASSWORD"

# Kredensial awal akun Super Admin untuk seeder lokal (wajib terdefinisi tanpa default)
if [ -z "${SEEDER_SUPERADMIN_EMAIL:-}" ]; then
    read -rp "Masukkan SEEDER_SUPERADMIN_EMAIL: " SEEDER_SUPERADMIN_EMAIL
    export SEEDER_SUPERADMIN_EMAIL
fi
set_env_key "SEEDER_SUPERADMIN_EMAIL" "$SEEDER_SUPERADMIN_EMAIL"

if [ -z "${SEEDER_SUPERADMIN_PASSWORD:-}" ]; then
    read -rsp "Masukkan SEEDER_SUPERADMIN_PASSWORD: " SEEDER_SUPERADMIN_PASSWORD
    echo
    export SEEDER_SUPERADMIN_PASSWORD
fi
set_env_key "SEEDER_SUPERADMIN_PASSWORD" "$SEEDER_SUPERADMIN_PASSWORD"

if [ -z "${SEEDER_SUPERADMIN_NAME:-}" ]; then
    read -rp "Masukkan SEEDER_SUPERADMIN_NAME [Super Admin Inisial]: " SEEDER_SUPERADMIN_NAME
    export SEEDER_SUPERADMIN_NAME="${SEEDER_SUPERADMIN_NAME:-Super Admin Inisial}"
fi
set_env_key "SEEDER_SUPERADMIN_NAME" "$SEEDER_SUPERADMIN_NAME"

if [ -z "${SEEDER_SUPERADMIN_NIP:-}" ]; then
    read -rp "Masukkan SEEDER_SUPERADMIN_NIP [198501012010011001]: " SEEDER_SUPERADMIN_NIP
    export SEEDER_SUPERADMIN_NIP="${SEEDER_SUPERADMIN_NIP:-198501012010011001}"
fi
set_env_key "SEEDER_SUPERADMIN_NIP" "$SEEDER_SUPERADMIN_NIP"

echo "Membangun container dan memulai service..."
docker compose build
# Gunakan --wait untuk memastikan PostgreSQL dan Redis mencapai status healthy sebelum migrasi
docker compose up -d --wait

echo "Layanan database dan antrean telah siap (healthy). Menjalankan migrasi dan seeder..."
docker compose exec app php artisan migrate --force
docker compose exec -e SEEDER_SUPERADMIN_EMAIL="$SEEDER_SUPERADMIN_EMAIL" \
                    -e SEEDER_SUPERADMIN_PASSWORD="$SEEDER_SUPERADMIN_PASSWORD" \
                    -e SEEDER_SUPERADMIN_NAME="$SEEDER_SUPERADMIN_NAME" \
                    -e SEEDER_SUPERADMIN_NIP="$SEEDER_SUPERADMIN_NIP" \
                    app php artisan db:seed --force

echo "Setup selesai. Aplikasi berjalan di http://localhost:8000"

