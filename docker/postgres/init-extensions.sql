-- Script inisialisasi ekstensi PostgreSQL otomatis saat container pertama kali dibuat
-- Ditempatkan di /docker-entrypoint-initdb.d/init-extensions.sql

CREATE EXTENSION IF NOT EXISTS "pgcrypto";
CREATE EXTENSION IF NOT EXISTS "pg_trgm";
CREATE EXTENSION IF NOT EXISTS "unaccent";

-- Verifikasi ekstensi terpasang
DO $$
BEGIN
    RAISE NOTICE 'Ekstensi pgcrypto, pg_trgm, dan unaccent berhasil diaktifkan.';
END $$;

