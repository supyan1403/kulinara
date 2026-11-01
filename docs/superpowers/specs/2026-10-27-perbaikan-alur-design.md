# Perbaikan Alur: Register, Kreator, Simpanan, Navigasi

Tanggal: 2026-10-27
Status: disetujui lisan, menunggu review spek

## Latar

Empat kekacauan alur dilaporkan pengguna di project kulinara (Laravel 13 + Inertia/React):

1. Pilihan peran di langkah 3 register membingungkan — Penikmat bisa upgrade sendiri kapan saja, jadi keputusan saat daftar terasa menentukan padahal tidak.
2. Alur "ajukan Kreator" terasa aneh — namanya pengajuan, tapi instan tanpa review; sedangkan penurunan pangkat disebut "harus diajukan ke Pengelola".
3. Resep tersimpan bisa 404 — resep yang turun ke Draft atau dihapus tetap muncul di daftar simpanan, padahal halaman detailnya memakai scope `terbit()`.
4. Navigasi tidak konsisten — menu "Peran" dan "FAQ" di `SiteHeader` berujung 404 di luar beranda; pengguna login di halaman publik hanya melihat tombol "Buka Dashboard".

Pendekatan yang dipilih: perbaikan bertahap (bukan redesign menyeluruh).

## Bagian 1 — Register 2 langkah + alur Kreator

### Register

- `resources/js/pages/Auth/Register.jsx`: hapus langkah 3 (kartu peran). `LANGKAH` jadi 2 entri: Identitas, Kata sandi. `data.role` dihapus dari `useForm`. `onError` tidak lagi merujuk `galat.role`.
- `RegisteredUserController::create()`: berhenti mengirim prop `roles`.
- `RegisteredUserController::store()`: selalu `UserRole::Penikmat`; `StoreRegistrationRequest` tidak lagi memvalidasi `role`.
- `User` fillable tetap memuat `role` (dipakai Panel & `jadiKreator`).

### Alur naik Kreator (dipertahankan)

- Route GET/POST `/studio-dapur/mulai` tetap — halaman konfirmasi sekali klik.
- `Studio/AjukanKreator.jsx`: rapikan bahasa. Hapus kalimat yang mengandaikan "Pilihan Menikmati resep saat mendaftar" (pilihan itu tak ada lagi); ganti penjelasan pembuka menjadi netral: apa yang didapat setelah menjadi Kreator. Peringatan "hanya bisa dilakukan sekali, turun pangkat harus ke Pengelola" tetap.
- `HandleInertiaRequests`: `can-become-creator` tetap `role === Penikmat`.

### Konsekuensi yang dipertahankan

- Panel Pengelola (`/panel/users`) tetap satu-satunya jalur menetapkan/menurunkan peran selain `jadiKreator`.
- Test disesuaikan: registrasi selalu menghasilkan `penikmat` (`tests/Feature/Auth/RegistrationTest.php`, `RoleAuthorizationTest.php`).

## Bagian 2 — Simpanan hanya resep terbit

- `SimpananController::index()`: saringan scope `terbit()` pada relasi — contoh: `->whereHas('resep', fn ($q) => $q->terbit())` pada `simpanan()` query, sehingga resep Draft/hapus tidak pernah tampil. `pluck('resep')->filter()` tetap sebagai pengaman null.
- `routes/web.php` `/dashboard`: `jumlahTersimpan` dihitung dengan saringan yang sama supaya angka = isi halaman.
- `simpanan.toggle` tidak berubah — sudah memakai scope `terbit()`.
- Tanpa migrasi, tanpa badge status.

## Bagian 3 — Navigasi

### Menu 404

- `Components/SiteHeader.jsx`: hapus "Peran" (`#peran`) dan "FAQ" (`#faq`) dari array `NAV`. Sisa: "Beranda" (`#`, → `/` di luar beranda) dan "Resep" (`#resep`, → `/resep`).

### Header login di halaman publik

- `SiteHeader` saat `user` ada: selain tombol "Buka Dashboard", tampilkan tautan "Simpanan" (`route('simpanan.index')`) ringkas — tanpa duplikasi menu lengkap Studio/Panel.
- Menu lengkap tetap milik `AuthenticatedLayout` (dashboard, studio, panel).

## Pengujian

- `php artisan test` — seluruh suite hijau.
- Fokus: `RegistrationTest` (tanpa field role → `penikmat`), `RoleAuthorizationTest` (alur jadi-kreator tetap), `SimpananTest` (tambah kasus: resep draft tidak muncul di daftar), `RouteNamesInJsTest`.
- Pemeriksaan visual manual: menu header di `/resep` dan `/resep/{slug}` (login & tamu) — tidak ada tautan 404.

## Di luar cakupan

- Redesign layout header menyeluruh / penyatuan halaman "Ruang Saya".
- Persetujuan Pengelola untuk pengajuan Kreator.
- Badge "tidak lagi tayang" di simpanan.
- Perbaikan beranda `routes/web.php` (closure besar) — tidak disentuh.
