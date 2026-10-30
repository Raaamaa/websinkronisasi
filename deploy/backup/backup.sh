#!/usr/bin/env bash
# ==============================================================================
# Skrip Otomatisasi Pencadangan (Backup) Produksi Websinkronisasi
# Referensi: NFR-08 (Backup Database PostgreSQL Harian & Sinkronisasi Bukti Dukung)
# ==============================================================================

set -euo pipefail

# Konfigurasi Direktori & Tanggal
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
DATE_DAY=$(date +"%Y%m%d")
BACKUP_DIR="${BACKUP_DIR:-/var/backups/websinkronisasi}"
DB_BACKUP_DIR="${BACKUP_DIR}/database"
FILES_BACKUP_DIR="${BACKUP_DIR}/files"
LOG_FILE="${BACKUP_DIR}/backup.log"
RETENTION_DAYS=30

# Konfigurasi Aplikasi & Database
APP_DIR="${APP_DIR:-/var/www/html}"
STORAGE_PRIVATE_DIR="${APP_DIR}/storage/app/private"
DB_HOST="${DB_HOST:-postgres}"
DB_PORT="${DB_PORT:-5432}"
DB_NAME="${DB_DATABASE:-websinkronisasi}"
DB_USER="${DB_USERNAME:-websinkronisasi_user}"
export PGPASSWORD="${DB_PASSWORD:-websinkronisasi_secret}"

# Inisialisasi folder backup
mkdir -p "${DB_BACKUP_DIR}" "${FILES_BACKUP_DIR}"

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "${LOG_FILE}"
}

log "=== Memulai Prosedur Backup Websinkronisasi (Timestamp: ${TIMESTAMP}) ==="

# 1. Backup Basis Data PostgreSQL (pg_dump)
DB_BACKUP_FILE="${DB_BACKUP_DIR}/db_${DB_NAME}_${TIMESTAMP}.sql.gz"
log "Mencadangkan database ${DB_NAME} ke ${DB_BACKUP_FILE}..."

if pg_dump -h "${DB_HOST}" -p "${DB_PORT}" -U "${DB_USER}" -d "${DB_NAME}" --clean --if-exists --no-owner --no-privileges | gzip -9 > "${DB_BACKUP_FILE}"; then
    DB_SIZE=$(du -h "${DB_BACKUP_FILE}" | cut -f1)
    log "Database berhasil dicadangkan. Ukuran: ${DB_SIZE}"
else
    log "GAGAL mencadangkan database PostgreSQL!"
    exit 1
fi

# 2. Backup Direktori Berkas Bukti Dukung Privat (NFR-04, NFR-08)
FILES_BACKUP_FILE="${FILES_BACKUP_DIR}/evidence_files_${TIMESTAMP}.tar.gz"
log "Mencadangkan direktori bukti fisik privat dari ${STORAGE_PRIVATE_DIR}..."

if [ -d "${STORAGE_PRIVATE_DIR}" ]; then
    tar -czf "${FILES_BACKUP_FILE}" -C "${APP_DIR}/storage/app" private
    FILES_SIZE=$(du -h "${FILES_BACKUP_FILE}" | cut -f1)
    log "Berkas bukti dukung berhasil dicadangkan. Ukuran: ${FILES_SIZE}"
else
    log "Peringatan: Direktori ${STORAGE_PRIVATE_DIR} tidak ditemukan atau kosong."
fi

# 3. Retensi & Pembersihan Cadangan Kedaluwarsa (> 30 Hari)
log "Membersihkan cadangan yang lebih tua dari ${RETENTION_DAYS} hari..."
find "${DB_BACKUP_DIR}" -type f -name "*.sql.gz" -mtime +"${RETENTION_DAYS}" -delete
find "${FILES_BACKUP_DIR}" -type f -name "*.tar.gz" -mtime +"${RETENTION_DAYS}" -delete

log "=== Prosedur Backup Selesai dengan Sukses ==="

