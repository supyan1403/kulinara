# Perbaikan Alur Kulinara Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Hilangkan kekacauan alur register/kreator, cegah simpanan 404, dan perbaiki navigasi header kulinara sesuai spek `docs/superpowers/specs/2026-10-27-perbaikan-alur-design.md`.

**Architecture:** Empat perbaikan independen dalam satu branch: (1) register jadi 2 langkah tanpa pilihan peran — semua pendaftar Penikmat; (2) halaman Ajukan Kreator tetap, bahasa dirapikan; (3) daftar simpanan & hitungan dashboard disaring scope `terbit()`; (4) `SiteHeader` buang menu 404, tambah tautan Simpanan untuk pengguna login. Tanpa migrasi DB.

**Tech Stack:** Laravel 13, Inertia.js (React JSX), PHPUnit 12, Tailwind. Komentar kode & pesan galat dalam Bahasa Indonesia — pertahankan gaya komentar eksisting (panjang, menjelaskan *mengapa*).

## Global Constraints

- Semua teks UI, pesan galat, dan komentar kode dalam Bahasa Indonesia.
- Jangan ubah route name yang ada (Ziggy dipakai frontend; lihat `RouteNamesInJsTest`).
- Jangan sentuh closure beranda di `routes/web.php` (di luar cakupan spek).
- `php artisan test` harus hijau penuh di akhir setiap task.
- Tanpa migrasi database baru.
- Spek: `docs/superpowers/specs/2026-10-27-perbaikan-alur-design.md`.

---

### Task 1: Backend register selalu Penikmat

**Files:**
- Modify: `app/Http/Requests/StoreRegistrationRequest.php`
- Modify: `app/Http/Controllers/Auth/RegisteredUserController.php`
- Test: `tests/Feature/Auth/RegistrationTest.php`, `tests/Feature/RoleAuthorizationTest.php`

**Interfaces:**
- Consumes: `UserRole::Penikmat` (enum eksisting), field `role` tetap di-fillable `User`.
- Produces: `POST /register` tanpa parameter `role` → user selalu `UserRole::Penikmat`. Frontend (Task 2) berhenti mengirim `role`.

- [ ] **Step 1: Tulis test baru yang gagal**

Tambah method ini di `tests/Feature/Auth/RegistrationTest.php`:

```php
    public function test_registration_always_creates_penikmat(): void
    {
        $this->post('/register', [
            'name' => 'Tanpa Role',
            'email' => 'tanparole@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $this->assertSame(
            UserRole::Penikmat,
            User::whereEmail('tanparole@example.com')->first()->role
        );
    }
```

- [ ] **Step 2: Jalankan test — FAIL**

Run: `php artisan test --filter=test_registration_always_creates_penikmat`
Expected: FAIL — "role" session error (validasi `required` masih ada).

- [ ] **Step 3: Ubah `StoreRegistrationRequest`**

Hapus import `App\Enums\UserRole` dan `Illuminate\Validation\Rule`, hapus aturan `role` dan pesan galat untuk `role`. Sisa `rules()`:

```php
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
```

Catatan: `Rule` tetap dipakai untuk `Rule::unique` — hanya import `UserRole` yang dihapus; `Rule::in` untuk role yang dibuang. `messages()` sisa: hanya `'password.confirmed'`.

- [ ] **Step 4: Ubah `RegisteredUserController`**

Di `create()`: hapus seluruh prop `'roles'` (array Penikmat/Kreator) dan komentar di atasnya; komentar `statistik` dipertahankan. Di `store()`:

```php
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            // Pendaftaran selalu Penikmat. Pilihan peran dihapus dari
            // formulir: Penikmat bisa naik Kreator sendiri lewat
            // /studio-dapur/mulai kapan saja, jadi keputusan saat mendaftar
            // tidak pernah menentukan apa pun. Pengelola tetap hanya
            // lewat Panel Pengelola.
            'role' => UserRole::Penikmat,
        ]);
```

Hapus import `UserRole` dari `store` usage? Tidak — `UserRole` masih dipakai di sini dan di `create()` mungkin tidak. Cek: setelah prop `roles` dihapus dari `create()`, `UserRole` tetap dibutuhkan `store()`. Import tetap.

- [ ] **Step 5: Jalankan test baru — PASS**

Run: `php artisan test --filter=test_registration_always_creates_penikmat`
Expected: PASS.

- [ ] **Step 6: Perbaiki test lama yang memakai `role`**

`tests/Feature/Auth/RegistrationTest.php`:
- `test_new_users_can_register`: hapus baris `'role' => UserRole::Penikmat->value,` dari payload.
- Hapus method `test_registering_as_chef_kreator_stores_the_role` (dialihkan oleh test baru).

`tests/Feature/RoleAuthorizationTest.php` — ganti tiga method ini:

```php
    public function test_pendaftaran_selalu_menghasilkan_penikmat(): void
    {
        $this->post(route('register'), [
            'name' => 'Pendaftar Baru',
            'email' => 'pendaftar@kulinara.test',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            // Kirim role untuk membuktikan server mengabaikannya.
            'role' => UserRole::Kreator->value,
        ])->assertRedirect(route('dashboard'));

        $this->assertSame(
            UserRole::Penikmat,
            User::whereEmail('pendaftar@kulinara.test')->first()->role
        );
    }

    public function test_pendaftaran_tidak_bisa_memilih_pengelola(): void
    {
        $this->post(route('register'), [
            'name' => 'Penyusup',
            'email' => 'penyusup@kulinara.test',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'role' => UserRole::Pengelola->value,
        ])->assertRedirect(route('dashboard'));

        // Field role diabaikan, bukan divalidasi — Pengelola tidak pernah
        // tercipta dari pendaftaran.
        $this->assertSame(
            UserRole::Penikmat,
            User::whereEmail('penyusup@kulinara.test')->first()->role
        );
    }
```

Hapus method `test_daftar_tanpa_role_ditolak` (tanpa role kini sah — memang tujuannya).

- [ ] **Step 7: Jalankan suite terkait — PASS**

Run: `php artisan test --filter="RegistrationTest|RoleAuthorizationTest"`
Expected: PASS semua.

- [ ] **Step 8: Commit**

```bash
git add app/Http/Requests/StoreRegistrationRequest.php app/Http/Controllers/Auth/RegisteredUserController.php tests/Feature/Auth/RegistrationTest.php tests/Feature/RoleAuthorizationTest.php
git commit -m "feat: pendaftaran selalu Penikmat, hapus pilihan peran dari registrasi

Co-authored-by: ByNara <admin@bynara.id>
Model: nara-cli"
```

---

### Task 2: Register frontend jadi 2 langkah

**Files:**
- Modify: `resources/js/pages/Auth/Register.jsx`

**Interfaces:**
- Consumes: props `roles` tidak lagi dikirim server (Task 1) — komponen tetap menerima default `roles = []` untuk kompatibilitas.
- Produces: formulir 2 langkah (Identitas → Kata sandi), payload `POST /register` tanpa field `role`.

- [ ] **Step 1: Ubah konstanta langkah**

Hapus entri peran dari `LANGKAH`:

```js
const LANGKAH = [
    { nomor: 1, label: 'Identitas' },
    { nomor: 2, label: 'Kata sandi' },
];
```

- [ ] **Step 2: Hapus `role` dari `useForm` dan signature props**

```js
export default function Register({ statistik = [] }) {
    const { data, setData, post, processing, errors, reset, setError, clearErrors } =
        useForm({
            name: '',
            email: '',
            password: '',
            password_confirmation: '',
        });
```

(Props `roles` dihapus dari signature — sudah tidak dikirim.)

- [ ] **Step 3: Hapus `galat.role` dari `onError`**

```js
            onError: (galat) => {
                if (galat.name || galat.email) setLangkah(1);
                else if (galat.password || galat.password_confirmation) setLangkah(2);
            },
```

- [ ] **Step 4: Ubah judul langkah**

Heading hanya punya dua kemungkinan sekarang:

```js
                {langkah === 1 && 'Siapa Anda?'}
                {langkah === 2 && 'Amankan akun Anda'}
```

- [ ] **Step 5: Hapus seluruh blok `{langkah === 3 && (...)}`**

Hapus JSX langkah 3 (fieldset kartu peran, tombol Kembali/Daftar di dalamnya) — langkah 2 yang jadi pengirim akhir. Ubah tombol submit langkah 2:

- `type="submit"` menaik `maju` — ganti handler langkah 2 agar memanggil `submit`:

```js
    const maju = (e) => {
        e.preventDefault();
        clearErrors();

        if (langkah === 1) {
            const galat = {};

            if (!data.name.trim()) galat.name = 'Nama lengkap wajib diisi.';
            if (!data.email.trim()) galat.email = 'Email wajib diisi.';
            else if (!POLA_EMAIL.test(data.email.trim()))
                galat.email = 'Format email belum benar, contoh: nama@contoh.com';

            if (Object.keys(galat).length > 0) {
                setError(galat);

                return;
            }

            setLangkah(2);

            return;
        }

        submit(e);
    };
```

Teks tombol langkah 2: `Daftar`. Hapus tombol "Kembali" langkah 2 (sudah langkah pertama) — atau pertahankan? Tidak ada yang bisa dikembalikan ke langkah 1 selain tombol mundur; PERTAHANKAN tombol Kembali (mundur ke identitas) — hanya ganti tombol kanan jadi `Daftar` dan pastikan tombol itu `type="submit"` (handler `maju` sudah memanggil `submit` di langkah 2).

- [ ] **Step 6: Hapus konstanta yang tidak terpakai**

Hapus `ROLE_ICONS`, `ROLE_DESCRIPTIONS`. (Jangan hapus `POLA_EMAIL`, `LANGKAH`, `LANGKAH` dipakai progress bar.)

- [ ] **Step 7: Verifikasi build frontend**

Run: `npm run build`
Expected: selesai tanpa error (Vite build sukses; warning ESLint mengenai tak terpakai tidak menggagalkan build — pastikan tidak ada `roles is not defined`).

- [ ] **Step 8: Commit**

```bash
git add resources/js/pages/Auth/Register.jsx
git commit -m "feat: register 2 langkah tanpa pilihan peran

Co-authored-by: ByNara <admin@bynara.id>
Model: nara-cli"
```

---

### Task 3: Rapikan bahasa halaman Ajukan Kreator

**Files:**
- Modify: `resources/js/pages/Studio/AjukanKreator.jsx`

**Interfaces:**
- Consumes: props `statistik` (`{resep, kategori}`) dari `StudioController::ajukan` — tidak berubah.
- Produces: tampilan sama, hanya copy yang berubah. Route & controller tidak disentuh.

- [ ] **Step 1: Ubah komentar file & paragraf pembuka**

Komentar atas komponen — ganti kalimat "membatalkan pilihan pendaftaran" (pilihan itu tak ada lagi):

```jsx
/**
 * Tujuan halaman ini satu: memberi jalan naik pangkat dari Penikmat ke Kreator.
 *
 * Pendaftaran selalu menghasilkan Penikmat, jadi halaman ini satu-satunya
 * pintu menuju Studio — dan karena itu ia wajib menyebutkan apa yang
 * benar-benar terjadi, termasuk bagian yang tidak bisa dibatalkan sendiri.
 */
```

Paragraf pembuka di dalam kartu — ganti judul & paragraf jadi:

```jsx
                            <h1 className="mt-4 text-2xl font-extrabold tracking-tight text-charcoal sm:text-3xl">
                                Saatnya resep Anda dibagikan.
                            </h1>

                            <p className="mt-3 max-w-2xl font-body text-base leading-relaxed text-muted">
                                Sebagai Penikmat Anda hanya menelusuri dan menyimpan.
                                Dengan satu ketukan, Studio Dapur terbuka — dan
                                {statistik?.resep ?? 0} resep dari {statistik?.kategori ?? 0} kategori sudah
                                menunggu di sana.
                            </p>
```

- [ ] **Step 2: Cek sisa referensi "mendaftar"**

Run: `grep -n "mendaftar" resources/js/pages/Studio/AjukanKreator.jsx`
Expected: kosong (komentar & paragraf lama yang menyebut "pilihan saat mendaftar" sudah diganti). Kotak peringatan turun pangkat ("hanya bisa dilakukan sekali... harus diajukan kepada Pengelola") dipertahankan apa adanya — masih valid karena Panel Pengelola memang satu-satunya jalan turun.

- [ ] **Step 3: Verifikasi build**

Run: `npm run build`
Expected: sukses.

- [ ] **Step 4: Commit**

```bash
git add resources/js/pages/Studio/AjukanKreator.jsx
git commit -m "docs: rapikan copy halaman ajukan Kreator setelah registrasi tanpa peran

Co-authored-by: ByNara <admin@bynara.id>
Model: nara-cli"
```

---

### Task 4: Simpanan hanya resep terbit (+ hitungan dashboard)

**Files:**
- Modify: `app/Http/Controllers/SimpananController.php`
- Modify: `routes/web.php` (closure `/dashboard` — hanya baris `jumlahTersimpan`)
- Test: `tests/Feature/SimpananTest.php`

**Interfaces:**
- Consumes: scope `Resep::scopeTerbit()` eksisting; relasi `User::simpanan()` (HasMany Simpanan).
- Produces: `GET /simpanan` → prop `resep[]` hanya resep terbit/terversifikasi; `GET /dashboard` → `jumlahTersimpan` dengan saringan sama.

- [ ] **Step 1: Tulis test yang gagal**

Tambah di `tests/Feature/SimpananTest.php`:

```php
    /**
     * Simpanan yang resepnya turun ke Draft disembunyikan otomatis —
     * halaman detail memakai scope `terbit()`, jadi kartu di daftar pasti
     * berujung 404. Menampilkannya berarti menjanjikan tautan mati.
     */
    public function test_resep_yang_turun_ke_draft_hilang_dari_daftar_simpanan(): void
    {
        $pembaca = User::factory()->create();
        $resep = Resep::factory()->create(['status' => 'terbit']);

        $this->actingAs($pembaca)->post(route('simpanan.toggle', $resep->id));

        // Penulis menurunkan resep ke draft setelah dibaca simpan.
        $resep->update(['status' => 'draft']);

        $this->actingAs($pembaca)
            ->get(route('simpanan.index'))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Simpanan/Index')
                ->has('resep', 0));
    }

    public function test_hitungan_dashboard_sama_dengan_isi_daftar_simpanan(): void
    {
        $pembaca = User::factory()->create();
        $tayang = Resep::factory()->create(['status' => 'terbit']);
        $draft = Resep::factory()->create(['status' => 'draft']);

        $this->actingAs($pembaca)->post(route('simpanan.toggle', $tayang->id));

        // Baris simpanan untuk draft dibuat langsung — toggle menolaknya,
        // tapi sisa data lama bisa mengandungnya.
        \App\Models\Simpanan::create([
            'user_id' => $pembaca->id,
            'resep_id' => $draft->id,
        ]);

        $this->actingAs($pembaca)
            ->get(route('dashboard'))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page->where('jumlahTersimpan', 1));
    }
```

- [ ] **Step 2: Jalankan — FAIL**

Run: `php artisan test --filter=test_resep_yang_turun_ke_draft_hilang_dari_daftar_simpanan`
Expected: FAIL — `has('resep', 0)` gagal (dapat 1).

- [ ] **Step 3: Saring `SimpananController::index`**

```php
            'resep' => $request->user()
                ->simpanan()
                // Saringan scope `terbit()`: resep yang turun ke Draft tidak
                // pernah tampil — halaman detailnya memakai scope yang sama,
                // jadi kartu di sini pasti berujung 404. Angka "Resep
                // tersimpan" di /dashboard memakai saringan identik supaya
                // keduanya tidak mungkin berbeda.
                ->whereHas('resep', fn ($q) => $q->terbit())
                ->with('resep.penulis:id,name')
                ->latest()
                ->get()
                ->pluck('resep')
                ->filter()
                ->values(),
```

- [ ] **Step 4: Saring hitungan dashboard di `routes/web.php`**

Ganti satu baris dalam closure `/dashboard`:

```php
        // Saringan sama dengan SimpananController::index — angka kartu =
        // isi halaman tujuannya, termasuk saat resep turun ke Draft.
        'jumlahTersimpan' => $request->user()->simpanan()
            ->whereHas('resep', fn ($q) => $q->terbit())
            ->count(),
```

- [ ] **Step 5: Jalankan test — PASS**

Run: `php artisan test --filter="SimpananTest"`
Expected: PASS semua (termasuk dua test baru).

- [ ] **Step 6: Jalankan suite penuh**

Run: `php artisan test`
Expected: PASS — tanpa kegagalan.

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/SimpananController.php routes/web.php tests/Feature/SimpananTest.php
git commit -m "fix: simpanan hanya tampilkan resep terbit, samakan hitungan dashboard

Co-authored-by: ByNara <admin@bynara.id>
Model: nara-cli"
```

---

### Task 5: SiteHeader — buang menu 404, tambah tautan Simpanan

**Files:**
- Modify: `resources/js/Components/SiteHeader.jsx`

**Interfaces:**
- Consumes: route name `simpanan.index` (eksisting, dipakai Ziggy); props `user`, `canLogin`, `canRegister`, `beranda` tidak berubah.
- Produces: menu publik hanya Beranda & Resep (valid di semua halaman); pengguna login melihat "Simpanan" + "Buka Dashboard".

- [ ] **Step 1: Perbaiki array `NAV`**

```js
// Hanya dua tautan yang valid di SETIAP halaman. "Peran" (#peran) dan
// "FAQ" (#faq) dulu di sini dan berujung 404 di luar beranda — fungsi
// arah() menerjemahkannya jadi /peran dan /faq yang tidak ada rutenya.
const NAV = [
    { label: 'Beranda', tautan: '#' },
    { label: 'Resep', tautan: '#resep' },
];
```

- [ ] **Step 2: Tambah tautan Simpanan untuk pengguna login**

Blok `user ? ... :` — sebelum tombol "Buka Dashboard", sisipkan tautan ringkas:

```jsx
                    {user ? (
                        <div className="flex items-center gap-2">
                            <Link
                                href={route('simpanan.index')}
                                className="rounded-lg px-4 py-2 text-sm font-semibold text-charcoal transition hover:text-primary"
                            >
                                Simpanan
                            </Link>
                            <Link
                                href={route('dashboard')}
                                className="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-dark"
                            >
                                Buka Dashboard
                            </Link>
                        </div>
                    ) : (
```

(Tombol "Buka Dashboard" yang lama dipertahankan persis; hanya dibungkus div + tautan Simpanan di depannya.)

- [ ] **Step 3: Verifikasi tak ada rute mati tersisa**

Run: `grep -n "label:" resources/js/Components/SiteHeader.jsx`
Expected: hanya `Beranda` dan `Resep`.

- [ ] **Step 4: Verifikasi build**

Run: `npm run build`
Expected: sukses.

- [ ] **Step 5: Commit**

```bash
git add resources/js/Components/SiteHeader.jsx
git commit -m "fix: SiteHeader buang menu 404, tambah tautan Simpanan bagi pengguna login

Co-authored-by: ByNara <admin@bynara.id>
Model: nara-cli"
```

---

### Task 6: Verifikasi akhir

**Files:**
- Tidak ada perubahan kode — hanya verifikasi.

- [ ] **Step 1: Suite penuh**

Run: `php artisan test`
Expected: semua PASS, 0 gagal.

- [ ] **Step 2: Build frontend**

Run: `npm run build`
Expected: sukses tanpa error.

- [ ] **Step 3: Cek manual (opsional bila dev server jalan)**

Run: `php artisan serve` (atau `composer dev`), buka `http://localhost:8000/register` — langkah 1 Identitas, tanpa kartu peran. Buka `/resep` sebagai login — header memuat "Simpanan" dan "Buka Dashboard", menu hanya Beranda/Resep.

- [ ] **Step 4: Laporkan hasil**

Ringkas: apa yang berubah, bukti test/build, sisa risiko (mis. tautan "Peran"/"FAQ" mungkin masih disebut teks di section beranda — hanya visual, bukan rute).

---

## Self-Review (penulis plan)

1. **Spek coverage:** Bagian 1 → Task 1+2+3; Bagian 2 → Task 4; Bagian 3 → Task 5; Pengujian → Task 6. Cakupan penuh.
2. **Placeholder scan:** tanpa TBD/TODO; setiap langkah kode punya blok kode atau perintah eksak.
3. **Konsistensi tipe:** props `roles` dihapus dari signature Task 2 `Register({ statistik = [] })` — konsisten dengan Task 1 yang berhenti mengirim `roles`. `whereHas('resep', fn ($q) => $q->terbit())` dipakai identik di Task 4 dua lokasi. `route('simpanan.index')` di Task 5 = name eksisting.

Catatan eksekusi: jika `RouteNamesInJsTest` memindai berkas JS untuk daftar route name, `simpanan.index` sudah dipakai sebelumnya (`AuthenticatedLayout`) — tidak ada nama baru yang ditambahkan.
