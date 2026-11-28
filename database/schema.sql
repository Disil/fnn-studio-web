-- 1. Generate database dan schema
CREATE DATABASE fnn
    WITH
    OWNER = postgres
    ENCODING = 'UTF8'
    LC_COLLATE = 'en_US.UTF-8'
    LC_CTYPE = 'en_US.UTF-8';

CREATE SCHEMA fnn;
SET search_path TO fnn, public;

-- 2. Tabel
-- 2a. Tabel untuk FNN sendiri (dan cabangnya)
CREATE TABLE IF NOT EXISTS unit_bisnis
(
    id SERIAL PRIMARY KEY,
    nama_unit VARCHAR(100) NOT NULL,
    lokasi VARCHAR(255)
);

CREATE TABLE IF NOT EXISTS jenis_layanan
(
    id      SERIAL PRIMARY KEY,
    unit_id INT          NOT NULL REFERENCES unit_bisnis (id) ON DELETE RESTRICT,
    kode    VARCHAR(50)  NOT NULL UNIQUE,
    nama    VARCHAR(100) NOT NULL
);

CREATE TABLE IF NOT EXISTS paket (
    id SERIAL PRIMARY KEY,
    jenis_layanan_id INT NOT NULL REFERENCES jenis_layanan(id) ON DELETE RESTRICT,
    harga_dasar NUMERIC(15, 2) NOT NULL CHECK (harga_dasar > 0),
    satuan VARCHAR(20) NOT NULL CHECK (satuan IN ('paket', 'menit', 'modul')),
    tipe VARCHAR(20) NOT NULL CHECK (tipe IN ('utama', 'tambahan')),
    deskripsi TEXT,
    is_active BOOLEAN NOT NULL DEFAULT TRUE
);

-- 2b. Tabel klien dan kontak (perwakilan klien)
CREATE TABLE IF NOT EXISTS klien
(
    id SERIAL PRIMARY KEY,
    nama_perusahaan VARCHAR(100) NOT NULL,
    alamat VARCHAR(255)
);

CREATE TABLE IF NOT EXISTS contact
(
    id     SERIAL PRIMARY KEY,
    id_klien      INTEGER REFERENCES klien (id),
    nama          VARCHAR(100) NOT NULL,
    jabatan       VARCHAR(50),
    nomor_telepon VARCHAR(20),
    email         VARCHAR(150) UNIQUE
);

--2c. Tabel user untuk sistem login, dan hak akses
CREATE TABLE IF NOT EXISTS users
(
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    role VARCHAR(20) NOT NULL CHECK (role IN ('owner', 'staff', 'kru', 'freelancer', 'klien')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    unit_id INT REFERENCES unit_bisnis(id) ON DELETE SET NULL,
    contact_id INT REFERENCES contact(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS staf
(
    user_id INT PRIMARY KEY REFERENCES users(id) ON DELETE CASCADE,
    jabatan VARCHAR(100) NOT NULL
);

CREATE TABLE IF NOT EXISTS kru
(
    user_id INT PRIMARY KEY REFERENCES users(id) ON DELETE CASCADE,
    tipe_kontrak VARCHAR(20) NOT NULL CHECK (tipe_kontrak IN ('internal', 'freelance')),
    keahlian VARCHAR(150)
);

--2d. projek dan transact utama
CREATE TABLE IF NOT EXISTS project
(
    id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    client_id INT NOT NULL REFERENCES klien(id) ON DELETE RESTRICT,
    dibuat_oleh INT REFERENCES users(id) ON DELETE SET NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'menunggu_konfirmasi' CHECK (
    status IN (
            'menunggu_konfirmasi',
            'meeting_konsep',
            'budgeting',
            'po_terbit',
            'produksi',
            'terkirim',
            'revisi',
            'selesai',
            'dibatalkan'
     )
    ),
    sumber VARCHAR(20) NOT NULL CHECK (sumber IN ('website', 'staff')),
    lokasi TEXT,
    tanggal_mulai DATE,
    tanggal_selesai DATE,
    deadline DATE,
    dibuat_pada TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS project_item
(
    id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    project_id INT NOT NULL REFERENCES project(id) ON DELETE CASCADE,
    package_id INT NOT NULL REFERENCES paket(id) ON DELETE RESTRICT,
    tipe_item VARCHAR(20) NOT NULL CHECK (tipe_item IN ('utama', 'tambahan')),
    kuantitas INT NOT NULL CHECK (kuantitas > 0),
    harga_satuan_snapshot NUMERIC(15, 2) NOT NULL CHECK (harga_satuan_snapshot >= 0)
);

CREATE TABLE IF NOT EXISTS project_contact (
    project_id INT NOT NULL REFERENCES project(id) ON DELETE CASCADE,
    contact_id INT NOT NULL REFERENCES contact(id) ON DELETE RESTRICT,
    peran VARCHAR(30) NOT NULL CHECK (peran IN ('pic', 'pengambil_keputusan', 'pihak_lain')),
PRIMARY KEY (project_id, contact_id, peran)
);

CREATE TABLE IF NOT EXISTS po (
    id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    project_id INT NOT NULL UNIQUE REFERENCES project(id) ON DELETE RESTRICT,
    nomor_po VARCHAR(100) NOT NULL UNIQUE,
    tanggal_po DATE NOT NULL,
    link_dokumen TEXT
);

CREATE TABLE IF NOT EXISTS project_crew (
    project_id INT NOT NULL REFERENCES project(id) ON DELETE CASCADE,
    user_id INT NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
    peran VARCHAR(100) NOT NULL,
PRIMARY KEY (project_id, user_id)
);

CREATE TABLE IF NOT EXISTS project_tahapan (
    id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    project_id INT NOT NULL REFERENCES project(id) ON DELETE CASCADE,
    nama_tahap VARCHAR(100) NOT NULL,
    urutan INT NOT NULL CHECK (urutan > 0),
    deadline_tahap DATE,
    selesai BOOLEAN NOT NULL DEFAULT FALSE
);

CREATE TABLE IF NOT EXISTS deliverable (
    id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    project_id INT NOT NULL REFERENCES project(id) ON DELETE CASCADE,
    versi INT NOT NULL CHECK (versi > 0),
    judul VARCHAR(150) NOT NULL,
    link TEXT NOT NULL,
    tipe VARCHAR(20) NOT NULL CHECK (tipe IN ('foto', 'video', 'render', 'file_final')),
    tahap VARCHAR(100),
    tampil_ke_klien BOOLEAN NOT NULL DEFAULT FALSE,
    tanggal_kirim TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (project_id, versi)
);

CREATE TABLE IF NOT EXISTS revisi (
    id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    project_id INT NOT NULL REFERENCES project(id) ON DELETE CASCADE,
    jenis VARCHAR(10) NOT NULL CHECK (jenis IN ('minor', 'mayor')),
    tanggal DATE NOT NULL DEFAULT CURRENT_DATE,
    sumber_feedback VARCHAR(100)
);

CREATE TABLE IF NOT EXISTS revisi_item (
    id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    revisi_id INT NOT NULL REFERENCES revisi(id) ON DELETE CASCADE,
    deskripsi TEXT NOT NULL,
status VARCHAR(20) NOT NULL DEFAULT 'terbuka' CHECK (status IN ('terbuka', 'selesai'))
);

CREATE TABLE IF NOT EXISTS kendala (
    id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    project_id INT NOT NULL REFERENCES project(id) ON DELETE CASCADE,
    kategori VARCHAR(20) NOT NULL CHECK (kategori IN ('teknis', 'non_teknis')),
    deskripsi TEXT NOT NULL,
    tanggal DATE NOT NULL DEFAULT CURRENT_DATE,
    status VARCHAR(30) NOT NULL DEFAULT 'terbuka'
);

CREATE TABLE IF NOT EXISTS payment (
    id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    project_id INT NOT NULL REFERENCES project(id) ON DELETE RESTRICT,
    dicatat_oleh INT NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
    jumlah NUMERIC(15, 2) NOT NULL CHECK (jumlah > 0),
    metode VARCHAR(20) NOT NULL CHECK (metode IN ('qris', 'transfer', 'tunai')),
    tanggal_bayar DATE NOT NULL DEFAULT CURRENT_DATE,
    bukti TEXT,
    catatan TEXT
);

CREATE TABLE IF NOT EXISTS project_log (
    id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    project_id INT NOT NULL REFERENCES project(id) ON DELETE CASCADE,
    status_lama VARCHAR(30),
    status_baru VARCHAR(30) NOT NULL,
    diubah_oleh INT REFERENCES users(id) ON DELETE SET NULL,
    diubah_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- PERINDEXANN
-- Mempercepat filter staf per unit bisnis (aturan akses unit_id) & relasi klien
CREATE INDEX idx_users_unit_id ON users(unit_id) WHERE unit_id IS NOT NULL;
CREATE INDEX idx_users_contact_id ON users(contact_id) WHERE contact_id IS NOT NULL;

-- Mempercepat relasi layanan & paket aktif
CREATE INDEX idx_jenis_layanan_unit_id ON jenis_layanan(unit_id);
CREATE INDEX idx_package_jenis_layanan_id ON paket(jenis_layanan_id);
CREATE INDEX idx_contact_client_id ON contact(id_klien);

-- Mempercepat filter dashboard berdasarkan status, klien, dan jadwal (Q3, Q6, Q8)
CREATE INDEX idx_project_client_id ON project(client_id);
CREATE INDEX idx_project_status ON project(status);
CREATE INDEX idx_project_tanggal_mulai ON project(tanggal_mulai) WHERE tanggal_mulai IS NOT NULL;

-- Mempercepat kalkulasi total tagihan & laporan per layanan (Q2, Q5, Q7, Q11, Q14)
CREATE INDEX idx_project_item_project_id ON project_item(project_id);
CREATE INDEX idx_project_item_package_id ON project_item(package_id);

-- Mempercepat cek bentrok jadwal & batas 1 proyek aktif freelancer (Trigger & Q4, Q8)
-- (user_id ditaruh di depan karena PK project_crew sudah meng-index project_id terlebih dahulu)
CREATE INDEX idx_project_crew_user_id ON project_crew(user_id);

-- Mempercepat query Q12 (mencari tahap aktif yang melewati deadline)
CREATE INDEX idx_project_tahapan_aktif ON project_tahapan(project_id, deadline_tahap)
    WHERE selesai = FALSE;

-- Mempercepat perhitungan total bayar (Trigger validasi bayar & Q1, Q2, Q10)
CREATE INDEX idx_payment_project_id ON payment(project_id);
CREATE INDEX idx_payment_tanggal_bayar ON payment(tanggal_bayar);

-- Mempercepat dashboard & laporan Q9, Q13, Q14
CREATE INDEX idx_revisi_project_id ON revisi(project_id);
CREATE INDEX idx_kendala_project_id ON kendala(project_id);
CREATE INDEX idx_project_log_project_id ON project_log(project_id);
