# PROJECT CONTEXT: FNN Studio — Sistem Manajemen Projek

## Ringkasan

Sistem web manajemen projek berbasis Laravel untuk mengelola klien, kontak, paket layanan, projek, PO, kru/freelancer, deliverable, revisi, kendala, dan pembayaran FNN Studio (Raya Kreatif Studio & Raya Kreatif Innovation). Tujuan akademik: membangun aplikasi basis data dengan prinsip DBMS (EERD → normalisasi → skema SQL → arsitektur MVC).

## Stack dan Batasan Teknis

- **Database**: PostgreSQL dengan custom schema `fnn`
- **Backend**: Laravel (PHP) menggunakan Eloquent ORM, Form Requests, Policies, dan Service Classes.
- **Frontend**: Blade Templates, Tailwind CSS, dan JavaScript vanilla / Alpine.js ringan.
- **Autentikasi & Otorisasi**: Laravel Auth (`web` guard) dengan hashing Bcrypt/Argon2 bawaan Laravel, serta Laravel Policies untuk kontrol akses berbasis role dan kepemilikan unit/projek.
- **Batasan Sistem**: Tidak ada payment gateway otomatis, webhook, fitur chat internal, DP, atau pembayaran termin/bertahap. File dokumen/deliverable/bukti bayar disimpan sebagai tautan eksternal (`TEXT`), bukan upload binary ke server/BLOB.
- **Single Source of Truth**: Ikuti struktur aktual `database/schema.sql` untuk nama tabel, kolom, relasi, dan constraint. Jangan mengasumsikan constraint database yang tidak tertulis di `schema.sql`.

## Konteks Bisnis

- Berlokasi di Ciracas, Jakarta Timur. Unit **Studio** melayani fotografi; unit **Innovation** mengerjakan multimedia, video, animasi 2D/3D, dan LMS (termasuk video HSSE).
- Kru unit Innovation mayoritas adalah freelancer; aturan bisnis menetapkan **satu freelancer hanya boleh memegang satu projek aktif** (status selain `selesai` dan `dibatalkan`) pada satu waktu.
- Alur hidup projek: `menunggu_konfirmasi` (jika dari website) → `meeting_konsep` → `budgeting` → `po_terbit` → `produksi` → `terkirim` → `revisi` (opsional) → `selesai` → pembayaran satu kali setelah hasil diterima.
- Satuan penagihan paket: `paket`, `menit` (untuk video/animasi), atau `modul` (untuk LMS). Harga pada `pakets` adalah harga acuan; harga kesepakatan dikunci pada `project_items.harga_satuan_snapshot`.
- Revisi dengan `jenis = 'mayor'` mengembalikan alur projek ke tahap `budgeting` untuk penyesuaian penawaran/addendum PO.
- Satu perusahaan klien dapat memiliki banyak kontak; peran kontak (`pic`, `pengambil_keputusan`, `pihak_lain`) ditetapkan pada level projek via tabel pivot `project_contact`.

## Aktor dan Hak Akses (RBAC)

Terapkan otorisasi melalui **Laravel Policies** dan query scoping pada Eloquent, bukan sekadar menyembunyikan elemen UI di Blade:

- `owner`: Akses penuh ke seluruh unit bisnis, katalog harga, manajemen user, projek, pembayaran, dan laporan.
- `staff`: Mengelola klien, kontak, projek, PO, dan pembayaran; akses projek dibatasi sesuai `unit_id` miliknya.
- `kru` & `freelancer`: Hanya melihat dan memperbarui progres teknis (tahapan, deliverable, status revisi, kendala) pada projek tempat mereka ditugaskan di `project_crew`. **Freelancer dilarang keras melihat data kontak klien dan data pembayaran.**
- `klien`: Akun terhubung ke `contact_id`; hanya melihat progres dan deliverable dengan `tampil_ke_klien = true` pada projek miliknya.

## Struktur Database Aktual (12 Tabel)

`database/schema.sql` menggunakan penamaan snake_case di dalam schema `fnn`:

1. `unit_bisnis(id, nama_unit, lokasi, timestamps)`
2. `jenis_layanan(id, unit_id, kode, nama, timestamps)`
3. `pakets(id, jenis_layanan_id, harga_dasar, satuan, tipe, deskripsi, is_active, timestamps)` — *Catatan: tidak memiliki kolom `nama`.*
4. `kliens(id, nama_perusahaan, alamat, timestamps)`
5. `contacts(id, klien_id, nama, jabatan, nomor_telepon, email, timestamps)` — *`klien_id` dan `nomor_telepon` bersifat nullable.*
6. `users(id, username, email, password, role, unit_id, contact_id, jabatan, tipe_kontrak, keahlian, remember_token, timestamps)` — *Menggabungkan entitas user, staf, dan kru.*
7. `projects(id, klien_id, dibuat_oleh, status, sumber, lokasi, tanggal_mulai, tanggal_selesai, deadline, nomor_po, tanggal_po, link_dokumen_po, timestamps)` — *Menggabungkan entitas projek dan PO.*
8. `project_items(id, project_id, paket_id, tipe_item, kuantitas, harga_satuan_snapshot, timestamps)` — *Total tagihan dihitung dinamis dari `SUM(kuantitas * harga_satuan_snapshot)`, jangan disimpan di kolom.*
9. `project_contact(project_id, contact_id, peran)` — *Tabel pivot many-to-many.*
10. `project_crew(project_id, user_id, peran)` — *Tabel pivot many-to-many.*
11. `project_tahapans(id, project_id, nama_tahap, urutan, deadline_tahap, selesai, timestamps)`
12. `deliverables(id, project_id, versi, judul, link, tipe, tahap, tampil_ke_klien, tanggal_kirim)` — *Pasangan `(project_id, versi)` unik; tanpa kolom `created_at`/`updated_at`.*
13. `project_revisions(id, project_id, jenis, sumber_feedback, deskripsi, status, timestamps)` — *Menggabungkan header revisi dan item revisi.*
14. `kendalas(id, project_id, kategori, deskripsi, tanggal, status, timestamps)`
15. `payments(id, project_id, dicatat_oleh, jumlah, metode, tanggal_bayar, bukti, catatan, timestamps)`
16. `project_logs(id, project_id, status_lama, status_baru, diubah_oleh, diubah_at)` — *Audit trail perubahan status projek.*

## Aturan Implementasi Laravel

- **Konvensi Model Eloquent**: Selalu definisikan `protected $table` secara eksplisit pada model yang tidak mengikuti jamak bahasa Inggris standar Laravel (seperti `unit_bisnis`, `jenis_layanan`, `project_contact`, `project_crew`). Nonaktifkan `$timestamps = false` pada model `Deliverable` dan `ProjectLog`.
- **Validasi Server-Side**: Gunakan `FormRequest` untuk memvalidasi semua enum `CHECK` (`status`, `role`, `satuan`, `tipe`, `sumber`, `peran`, `metode`, dll.) dan aturan numerik (`harga_dasar > 0`, `kuantitas > 0`, `jumlah > 0`).
- **Logika Bisnis di Service / Observer**:
  - Gunakan `ProjectObserver` untuk otomatis menulis baris baru ke `project_logs` setiap kali `projects.status` berubah.
  - Gunakan Service Class saat menambah `project_crew` untuk memblokir penugasan jika `role = 'freelancer'` sudah memiliki projek aktif.
  - Gunakan Service Class saat mencatat `payments` untuk memvalidasi agar total pembayaran tidak melebihi total tagihan `project_items`.
- **Penanganan Foreign Key `RESTRICT`**: Tangkap `Illuminate\Database\QueryException` (khususnya SQLSTATE `23503`) saat menghapus `unit_bisnis`, `jenis_layanan`, `pakets`, `kliens`, atau `projects` yang memiliki PO/pembayaran, dan tampilkan pesan error yang informatif kepada pengguna.