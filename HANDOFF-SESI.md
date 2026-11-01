# HANDOFF — Sesi OpenCode Kulinara (baca ini dulu di sesi baru)

> Dokumen ini ditulis otomatis di akhir sesi pindah-ke-Vercel. Sesi baru: baca ini, jangan mulai dari nol.

## Status SAAT INI

- ✅ Database PostgreSQL lokal → **Supabase ter-import** (649 user, 385 resep, 20.714 suka)
- ✅ `.env` sudah nunjuk Supabase (`db.hpewndywmecptdvtjhqs.supabase.co:5432`, db `postgres`, user `postgres`)
- ✅ `composer.json`: PHP `^8.4` + `ext-pdo` + `ext-pdo_pgsql`, `composer update` sukses (2 advisori dev, non-kritis)
- ✅ Ekstensi PHP lokal aktif: `pdo`, `pdo_pgsql`, `pgsql`
- ✅ `npm run build` sukses (Vite 8/rolldown, ~10 dtk)
- ✅ `api/index.php` dibuat (salinan `public/index.php` — path `__DIR__.'/../'` sudah benar)
- ✅ `vercel.json` dibuat (perlu diverifikasi JSON valid)
- ✅ Project **dipindah** dari `D:\laragon\www\kulinara` → **`D:\myProject\kulinara`** (copy 31.852 file, 494 MB, identik)
- ⏳ Folder lama `D:\laragon\www\kulinara` BELUM dihapus (terkunci sesi + IDE Gemini agy.exe `--add-dir`)
- ❌ Git: BELUM init, BELUM commit, BELUM push

## BELUM SELESAI (urutan lanjutan sesi baru)

1. **Verifikasi JSON** `vercel.json`: `Get-Content vercel.json -Raw | ConvertFrom-Json`
2. **`.gitignore` — TAMBAH (kredensial!):**
   ```
   backup-*.dump
   backup-*.sql
   /motion/
   /video/
   ```
   (`.env`, `vendor/`, `node_modules/`, `storage/*.key`, `public/build` SUDAH ada)
3. **`git config --global user.email/user.name`** → pakai `supyan1403@users.noreply.github.com` / `supyan1403`
4. **`git init -b main`** → `git add .` → **AUDIT `git status`** (pastikan TIDAK ada `.env` / `backup-kulinara.dump`) → `git commit -m "refactor: complete vercel php 8.4 and supabase setup"`
5. **Repo GitHub PRIVATE** di github.com/supyan1403 (gh CLI v2.96.0 SUDAH terinstall, cek `gh auth status` — kemungkinan belum login) → `gh auth login` atau buat repo manual → `git remote add origin` → `git push -u origin main`
6. **Lanjut ke Vercel**: set env vars (lihat daftar di bawah), `vercel --prod`

## Rencana Vercel (disetujui)

- Runtime `vercel-php@0.8.0` → `api/index.php`
- Routes: `/build/(.*)→/public/build/$1`, sisanya → `/api/index.php`
- Cache override ke `/tmp` (read-only FS): `bootstrap/app.php` tambah `if (getenv('VERCEL')) { $app->useStoragePath(sys_get_temp_dir()); }` ATAU via env vars
- Env vars Vercel:
  ```
  APP_ENV=production
  APP_DEBUG=false
  APP_KEY=<php artisan key:generate --show>
  DB_CONNECTION=pgsql
  DB_HOST=db.hpewndywmecptdvtjhqs.supabase.co
  DB_PORT=5432
  DB_DATABASE=postgres
  DB_USERNAME=postgres
  DB_PASSWORD=<password Supabase — BUKAN 140304sauri>
  CACHE_STORE=array
  SESSION_DRIVER=cookie
  APP_CONFIG_CACHE=/tmp/config.php
  APP_EVENTS_CACHE=/tmp/events.php
  APP_PACKAGES_CACHE=/tmp/packages.php
  APP_ROUTES_CACHE=/tmp/routes.php
  APP_SERVICES_CACHE=/tmp/services.php
  VIEW_COMPILED_PATH=/tmp/views
  ```
- `config/queue.php`: default `database` → harus diubah `sync` (Vercel tanpa worker)
- Risiko: vercel-php community runtime, serverless cold start, file upload `storage/` read-only (pindah ke Supabase Storage), scheduler tidak jalan

## Kredensial / Fakta Penting

- **Postgres lokal** user `postgres` password `140304sauri` (atau `140304sauri?` — lihat `.env`) — dipakai pgAdmin lokal; password SUPABASE berbeda (set via Dashboard → Project Settings → Database → Set database password)
- **pg_dump lokal**: `C:\Program Files\PostgreSQL\18\bin\pg_dump.exe` (TIDAK di PATH)
- **Backup dump**: `D:\laragon\www\kulinara\backup-kulinara.dump` (334 KB, custom format) — sudah di-restore ke Supabase, ada juga di copy folder baru kalau sudah pindah
- **Supabase project ref**: `hpewndywmecptdvtjhqs`, host `db.hpewndywmecptdvtjhqs.supabase.co`, port 5432 (Direct connection), pooler port 6543
- **gh CLI** v2.96.0 terinstall (`gh` alias OK), auth BELUM dicek
- **Server lokal**: `php -S 127.0.0.1:8000` via `jalan.bat`, domain `kulinara.kyronix.my.id` = tunnel ke server itu
- **Login test**: `pengguna@kulinara.test` / `password` (Sari, id 168)
- **FFmpeg/ffprobe**: `C:\Users\MyBook Z Series\AppData\Local\Microsoft\WinGet\Links\` (PATH shell gak ke-refresh)
- **Chrome**: `C:\Program Files\Google\Chrome\Application\chrome.exe`

## Konteks Proyek (fitur yang sudah jalan, jangan di-regresi)

- **Profil `/pengguna/{username}`**: mode `koleksi` (owner: tabs Disimpan/Disukai/Unggulan + statsKoleksi) vs `publik` (orang lain: showcase resep owner, tabs Terbaru/Unggulan/Paling Disukai/Paling Disimpan), pagination client-side 12/halaman
- **`/simpanan`**: server-side paginate 12, prop `paginasi`
- **Studio Dapur**: card grid sama persis `/resep` (`gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4`), per-recipe ❤️/🔖 counts, dropdown urutkan client-side, stat cards "Kinerja resep tayang"
- **Dashboard**: `panelStudio` cuma muncul kalau `jumlahResep === 0`
- **Model `Suka`**: tanpa morphTo — `resep(): BelongsTo(Resep, 'sukaable_id')`; filter type di PARENT query, BUKAN di relation; `loadCount` butuh EloquentCollection
- **Studio stats**: suka/simpanan cuma dari resep tayang; `resep.user_id` harus di-qualified (join ambiguity)
- **Dummy data**: `PopulasiEngagementSeeder` (suka resep/profil + simpanan site-wide)
- **15 route sudah lolos HTTP kernel test**

## Video Promo (MOTION — user sudah bilang "lupakan perihal motion")

Arsip saja, JANGAK lanjut kecuali diminta lagi:
- Master: `video/kulinara-promo.mp4` (45 dtk, 53 MB, H.264+AAC, VO Gadis + musik "Just Chill" Mixkit)
- Web: `public/video/kulinara-promo.mp4` (9,4 MB) + poster `.jpg` — **SUDAH ter-embed di `Welcome.jsx`** (section "Lihat Kulinara Bergerak" + modal fullscreen)
- Source: `motion/index.html` (DURATION=45, 4 klip footage `klip-*.mp4`), render tools `export.mjs`/`snap.mjs`/`verify.mjs`
- `motion/server.mjs` ada hardcode path `D:\\laragon\\www\\kulinara` → ganti `D:\\myProject\\kulinara` kalau dipakai lagi
- Server Range-nya (node server.mjs port 5178) diperlukan supaya video bisa di-seek (php -S gak dukung HTTP Range)

## Cara Build / Test

- Build: `.\bangun.bat` (vite build) — user HARUS hard reload `Ctrl+Shift+R` setelah (masalah cache browser berulang)
- Belum ada git repo → semua perubahan selama ini UNCOMMITTED, ada di copy `D:\myProject\kulinara`
