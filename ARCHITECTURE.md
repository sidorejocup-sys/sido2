# Architecture, Database Schema & Security Specifications

## Overview

This project is designed for PostgreSQL with strict relational integrity, strong typing, and security-focused data handling.

- PostgreSQL is the target database engine.
- Strict types are used to avoid floating-point and format ambiguity.
- All monetary values use `DECIMAL(15,2)`.
- Foreign key constraints are enforced with `ON UPDATE CASCADE` and `ON DELETE RESTRICT`.
- Passwords are stored as hashed values using Laravel's built-in hashed cast.

## Data Model

### users

- `id` — auto-incrementing primary key
- `username` — `VARCHAR`, unique
- `email` — `VARCHAR`, unique
- `password` — hashed string
- `role` — `ENUM('super_admin','kades','kasun_rw','rt','pengguna')`
- `created_at`, `updated_at`

### subjek_pajak

- `NIK` — `VARCHAR(16)`, primary key
- `nama` — `VARCHAR(150)`
- `alamat` — `TEXT`
- `RT` — `VARCHAR(3)`
- `RW` — `VARCHAR(3)`
- `no_hp` — `VARCHAR(15)`
- `created_at`, `updated_at`

### objek_pajak

- `nop` — `VARCHAR(18)`, primary key
- `nik_pemilik` — `VARCHAR(16)`, foreign key to `subjek_pajak.NIK`
- `letak_objek` — `TEXT`
- `luas_bumi` — `INT`
- `luas_bangunan` — `INT`
- `status_aktif` — `BOOLEAN`, default `true`
- `created_at`, `updated_at`

### sppt

- `id_sppt` — primary key
- `nop` — `VARCHAR(18)`, foreign key to `objek_pajak.nop`
- `tahun` — `INT`
- `njop_bumi` — `DECIMAL(15,2)`
- `njop_bangunan` — `DECIMAL(15,2)`
- `pajak_terhutang` — `DECIMAL(15,2)`
- `status_bayar` — `ENUM('piutang','proses_pengajuan','lunas','ditolak')`
- `created_at`, `updated_at`

### riwayat_mutasi

- `id_mutasi` — primary key
- `nop_asal` — `VARCHAR(18)`
- `nik_lama` — `VARCHAR(16)`
- `nik_baru` — `VARCHAR(16)`
- `jenis_mutasi` — `VARCHAR(50)`
- `tgl_mutasi` — `DATE`
- `no_arsip` — `VARCHAR(100)`
- `created_at`

### pembayaran

- `id_bayar` — primary key
- `id_sppt` — foreign key to `sppt.id_sppt`
- `tgl_bayar` — `TIMESTAMP`
- `jumlah_bayar` — `DECIMAL(15,2)`
- `id_petugas` — foreign key to `users.id`
- `created_at`

## Security and Integrity

- `password` uses Laravel's `'hashed'` cast to ensure storage as a secure one-way hash.
- `ENUM` columns limit allowed values for roles and payment status.
- `DECIMAL(15,2)` prevents floating-point rounding errors in financial data.
- Foreign key constraints with `RESTRICT` on delete prevent accidental data loss when parent records are referenced.
- `created_at` and `updated_at` timestamps provide audit data for key entities.

## Implementation

The schema is implemented in a new migration: `database/migrations/2026_05_31_000003_create_pajak_schema.php`.

The domain models are implemented in:

- `app/Models/SubjekPajak.php`
- `app/Models/ObjekPajak.php`
- `app/Models/Sppt.php`
- `app/Models/RiwayatMutasi.php`
- `app/Models/Pembayaran.php`

`App\Models\User` has been extended with `username` and `role` support.
