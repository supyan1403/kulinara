import { chromium } from 'playwright';

/**
 * E2E landing page — memastikan kontrol yang dulu MATI sekarang benar-benar
 * bekerja: kolom pencarian hero, chip kategori, dan tombol "Lihat Semua" yang
 * mengarah ke direktori nyata. Juga memverifikasi klaim angka palsu sudah
 * lenyap, nama navigasi konsisten, animasi reveal tidak meninggalkan elemen
 * transparan, slideshow etalase hero (dots, rotasi otomatis, jeda saat hover),
 * direktori /resep yang terbuka tanpa login beserta paginasinya, dan jembatan
 * dari kartu resep ke halaman detailnya.
 *
 * Pakai:
 *   node scripts/e2e-landing.mjs
 */

const BASE = process.env.BASE_URL ?? 'http://127.0.0.1:8000';

const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: 1440, height: 900 } });

const errors = [];
page.on('console', (m) => m.type() === 'error' && errors.push(m.text()));
page.on('pageerror', (e) => errors.push(`PAGEERROR: ${e.message}`));

const hasil = [];
const cek = (nama, lolos, detail = '') => {
    hasil.push({ nama, lolos, detail });
    console.log(`${lolos ? 'OK  ' : 'GAGAL'} ${nama}${detail ? ` — ${detail}` : ''}`);
};

/**
 * Inertia menavigasi lewat XHR, jadi page.url() berubah beberapa saat setelah
 * klik. Fungsi ini menunggu query string yang diharapkan benar-benar muncul
 * supaya pemeriksaan tidak terbaca gagal karena terlalu cepat.
 */
const tungguQuery = async (uji, batasMs = 8000) =>
    page
        .waitForFunction(uji, null, { timeout: batasMs })
        .then(() => true)
        .catch(() => false);

const isiKolomCari = 'form[role="search"] input[type="search"]';
const tombolCari = 'form[role="search"] button[type="submit"]';
const teksHitung = 'p[aria-live="polite"]';

/* ── 1. Kolom pencarian hero benar-benar navigasi ── */
await page.goto(`${BASE}/`, { waitUntil: 'networkidle' });
await page.fill(isiKolomCari, 'ayam');
await page.click(tombolCari);
await tungguQuery(() => location.search.includes('q=ayam'));

const urlCari = new URL(page.url());
cek('pencarian mengirim ?q=', urlCari.searchParams.get('q') === 'ayam', page.url());

const isiInput = await page.inputValue(isiKolomCari);
cek('nilai pencarian bertahan setelah submit', isiInput === 'ayam', `input="${isiInput}"`);

const hitungCari = (await page.textContent(teksHitung))?.trim() ?? '';
cek('hitungan hasil tampil', /\d+ dari \d+ resep/.test(hitungCari), hitungCari);

const kartuCari = await page.locator('#resep article').count();
cek('kartu hasil pencarian tampil', kartuCari > 0, `${kartuCari} kartu`);

/* ── 2. Chip kategori mempertahankan pencarian ── */
const chipJajanan = page.locator('#resep button', { hasText: 'Jajanan Pasar' });
await chipJajanan.click();
await tungguQuery(() => location.search.includes('kategori=jajanan'));

const urlChip = new URL(page.url());
cek(
    'chip membawa saringan kategori',
    urlChip.searchParams.get('kategori') === 'jajanan',
    page.url(),
);
cek(
    'chip mempertahankan pencarian',
    urlChip.searchParams.get('q') === 'ayam',
    page.url(),
);

const pillAktif = await page.locator('#resep button', { hasText: 'Kata kunci' }).count();
cek('pill hapus pencarian tampil', pillAktif === 1, `${pillAktif} pill`);

/* ── 3. Pill hapus pencarian mengembalikan hasil kategori ── */
await page.locator('#resep button', { hasText: 'Kata kunci' }).click();
await tungguQuery(() => !location.search.includes('q='));

const urlTanpaQ = new URL(page.url());
cek(
    'pill menghapus ?q= tapi kategori tetap',
    urlTanpaQ.searchParams.get('q') === null &&
        urlTanpaQ.searchParams.get('kategori') === 'jajanan',
    page.url(),
);

// "Lihat Semua" harus meneruskan saringan yang sedang berjalan, bukan
// mengembalikan pengunjung ke direktori tanpa saringan sama sekali.
const hrefSemuaTersaring = await page
    .locator('a', { hasText: 'Lihat Semua' })
    .first()
    .getAttribute('href');
cek(
    'Lihat Semua meneruskan saringan aktif',
    hrefSemuaTersaring?.includes('kategori=jajanan') === true,
    hrefSemuaTersaring ?? '',
);

/* ── 4. Tidak ada lagi kontrol mati / klaim angka palsu ── */
await page.goto(`${BASE}/`, { waitUntil: 'networkidle' });

const isiHalaman = await page.textContent('body');
cek('klaim "12.000+" sudah lenyap', !isiHalaman.includes('12.000'), '');
cek('klaim "ribuan" sudah lenyap', !isiHalaman.includes('ribuan'), '');

const bookmark = await page.locator('button[aria-label^="Simpan"]').count();
cek('tombol bookmark mati sudah dihapus', bookmark === 0, `${bookmark} tombol`);

// "Lihat Semua" yang dulu mati kembali hidup, dan menunjuk ke direktori yang
// sudah nyata ada (rute resep.index).
const lihatSemua = page.locator('a', { hasText: 'Lihat Semua' });
const jumlahLihatSemua = await lihatSemua.count();
cek('link "Lihat Semua" tampil', jumlahLihatSemua === 1, `${jumlahLihatSemua} link`);

const hrefLihatSemua = jumlahLihatSemua === 1 ? await lihatSemua.first().getAttribute('href') : null;
// Ziggy menghasilkan URL absolut, jadi yang dibandingkan adalah pathname-nya.
const pathLihatSemua = hrefLihatSemua ? new URL(hrefLihatSemua, BASE).pathname : null;
cek(
    'Lihat Semua menuju /resep tanpa saringan',
    pathLihatSemua === '/resep' && !hrefLihatSemua?.includes('?'),
    hrefLihatSemua ?? '(tidak ada)',
);

// Klaim yang tidak bisa dipenuhi aplikasi harus tetap tidak ada — sekarang
// halaman ini jujur, bukan menjanjikan hal yang belum pernah dibangun.
for (const kata of ['presisi', 'kurator gizi profesional', 'deterministik', 'Hak Cipta Dilindungi']) {
    cek(`klaim "${kata}" sudah lenyap`, !isiHalaman.includes(kata), '');
}
cek(
    'hero menyebut sumber angka gizi',
    isiHalaman.includes('Nilai gizi mengacu TKPI'),
    '',
);
cek('section "03 · Alur" sudah dipotong', !isiHalaman.includes('· Alur'), '');

/* ── 5. Semua chip kategori tidak pernah kosong ── */
const daftarChip = await page.locator('#resep button').allTextContents();
for (const isi of daftarChip) {
    const chip = page.locator('#resep button', { hasText: isi.trim() }).first();
    await chip.click();

    // Tunggu sampai chip benar-benar jadi aktif — Inertia menavigasi lewat XHR,
    // jadi delay tetap rentan terhadap jaringan yang sedang lambat.
    await page
        .waitForFunction(
            (teks) =>
                [...document.querySelectorAll('#resep button[aria-pressed]')].some(
                    (b) => b.textContent.trim() === teks && b.getAttribute('aria-pressed') === 'true',
                ),
            isi.trim(),
            { timeout: 8000 },
        )
        .catch(() => false);
    await page.waitForTimeout(200);

    const adaKartu = await page.locator('#resep article').count();
    const kosong = await page.locator('#resep p.rounded-2xl').count();
    const isi2 = (await page.textContent(teksHitung))?.trim() ?? '';
    const semua = isi.trim() === 'Semua Resep';

    // Chip selain "Semua Resep" wajib menghasilkan saringan — kalau teksnya
    // masih "21 resep · 6 kategori" berarti navigasi chip gagal.
    const tersaring = semua ? /\d+ resep · \d+ kategori/.test(isi2) : /\d+ dari \d+ resep/.test(isi2);

    cek(
        `chip "${isi.trim()}" berisi`,
        tersaring && (adaKartu > 0 || kosong > 0),
        `${adaKartu} kartu · ${isi2}`,
    );
    if (kosong > 0) console.log(`      ⚠ ${await page.textContent('#resep p.rounded-2xl')}`);
}

/* ── 6. Nama navigasi konsisten (tidak ada lagi "3 Peran") ── */
const labelNav = await page
    .locator('nav.md\\:flex a')
    .allTextContents()
    .then((t) => t.map((s) => s.trim()));
cek(
    'label navbar Beranda·Resep·Peran·FAQ',
    JSON.stringify(labelNav) === JSON.stringify(['Beranda', 'Resep', 'Peran', 'FAQ']),
    labelNav.join(' · '),
);
cek('label "3 Peran" sudah lenyap', !isiHalaman.includes('3 Peran'), '');

const isiFooter = (await page.textContent('footer'))?.trim() ?? '';
cek(
    'footer memakai "Peran", bukan "Tiga Peran"',
    isiFooter.includes('Peran') && !isiFooter.includes('Tiga Peran'),
    isiFooter.includes('Tiga Peran') ? 'masih ada "Tiga Peran"' : 'konsisten',
);

/* ── 7. Tombol Cari Resep memakai primary hijau, bukan oranye ── */
const warnaTombol = await page
    .locator(tombolCari)
    .first()
    .evaluate((el) => getComputedStyle(el).backgroundColor);
cek('tombol Cari Resep hijau primary', warnaTombol === 'rgb(4, 77, 60)', warnaTombol);

/* ── 8. Animasi reveal — tidak ada elemen yang tersangkut transparan ── */
await page.evaluate(async () => {
    const tinggi = document.body.scrollHeight;
    for (let y = 0; y <= tinggi; y += 400) {
        window.scrollTo(0, y);
        await new Promise((r) => setTimeout(r, 60));
    }
    window.scrollTo(0, tinggi);
});
await page.waitForTimeout(600);

const tersangkut = await page.locator('[data-reveal].opacity-0').count();
cek('semua elemen reveal sudah tampil', tersangkut === 0, `${tersangkut} tersangkut`);

const jumlahReveal = await page.locator('[data-reveal]').count();
cek('animasi reveal terpasang di landing', jumlahReveal >= 15, `${jumlahReveal} elemen`);

/* ── 9. Slideshow hero: dots, rotasi otomatis, dan jeda saat hover ── */
// Kembali ke halaman tanpa saringan — bagian sebelumnya meninggalkan chip
// kategori aktif, dan pool slideshow memang sengaja mengikuti saringan.
await page.goto(`${BASE}/`, { waitUntil: 'networkidle' });

const judulHero = () =>
    page
        .locator('h3.line-clamp-2')
        .first()
        .textContent()
        .then((t) => t?.trim());

const dots = page.locator('[aria-label="Pilih resep unggulan"] button');
const jumlahDots = await dots.count();
cek('dots slideshow tampil', jumlahDots > 1, `${jumlahDots} dots`);

// Indeks awal slideshow memang diacak, jadi targetnya harus dot yang BUKAN
// sedang aktif. Mengklik dot aktif tidak mengubah apa pun, dan uji semacam ini
// akan gagal sesekali hanya karena angka acak kebetulan jatuh di dot terakhir.
const indeksAktif = await dots.evaluateAll((els) =>
    els.findIndex((el) => el.getAttribute('aria-current') === 'true'),
);
const indeksTujuan = (indeksAktif + 1) % jumlahDots;

const judulAwal = await judulHero();
await dots.nth(indeksTujuan).click();
await page.waitForTimeout(900);
const judulSetelahKlik = await judulHero();
cek(
    'klik dots mengganti slide',
    judulSetelahKlik !== judulAwal,
    `${judulAwal} -> ${judulSetelahKlik}`,
);

// Lepas kursor dari kartu dulu — mengklik dot tadi membuat putaran berhenti.
await page.mouse.move(10, 10);
await page.waitForTimeout(4800); // > interval 4 detik
const judulSetelahTunggu = await judulHero();
cek(
    'slide berganti otomatis tanpa disentuh',
    judulSetelahTunggu !== judulSetelahKlik,
    `${judulSetelahKlik} -> ${judulSetelahTunggu}`,
);

// WCAG 2.2.2: konten yang bergerak otomatis wajib bisa dijeda.
const kotakJudul = await page.locator('h3.line-clamp-2').first().boundingBox();
if (kotakJudul) {
    await page.mouse.move(kotakJudul.x + kotakJudul.width / 2, kotakJudul.y + 8);
}
await page.waitForTimeout(300);
const judulSaatHover = await judulHero();
await page.waitForTimeout(4800);
const judulSetelahHover = await judulHero();
cek(
    'putaran berhenti saat kursor di kartu (WCAG 2.2.2)',
    judulSetelahHover === judulSaatHover,
    `${judulSaatHover} -> ${judulSetelahHover}`,
);

// Pengguna "reduce motion" tidak boleh mendapat putaran otomatis sama sekali.
const konteksTenang = await browser.newContext({ reducedMotion: 'reduce' });
const halamanTenang = await konteksTenang.newPage();
await halamanTenang.goto(`${BASE}/`, { waitUntil: 'networkidle' });

const judulTenang = (await halamanTenang.locator('h3.line-clamp-2').first().textContent())?.trim();
await halamanTenang.waitForTimeout(4800);
const judulTenangSetelah = (await halamanTenang
    .locator('h3.line-clamp-2')
    .first()
    .textContent())?.trim();

cek(
    'reduce motion: tanpa putaran otomatis',
    judulTenangSetelah === judulTenang,
    `${judulTenang} -> ${judulTenangSetelah}`,
);
await konteksTenang.close();

/* ── 10. Direktori /resep — terbuka tanpa login dan berpaginasi ── */
await page.goto(`${BASE}/resep`, { waitUntil: 'networkidle' });

const judulDirektori = (await page.locator('h1').first().textContent())?.trim() ?? '';
cek(
    'direktori /resep terbuka tanpa login',
    judulDirektori === 'Semua Resep Nusantara',
    judulDirektori || '(tidak ada h1 — kemungkinan dialihkan ke /login)',
);

const kartuDirektori = await page.locator('main article').count();
cek('direktori menampilkan 12 kartu per halaman', kartuDirektori === 12, `${kartuDirektori} kartu`);

const ringkasan = (
    await page.locator('nav[aria-label="Navigasi halaman"] p').first().textContent()
)?.trim() ?? '';
cek('ringkasan paginasi menyebut jangkauan', /Menampilkan \d+–\d+ dari \d+ resep/.test(ringkasan), ringkasan);

const totalDirektori = Number((ringkasan.match(/dari (\d+) resep/) ?? [])[1] ?? 0);
const adaHal2 = await page
    .locator('nav[aria-label="Navigasi halaman"] a:text-is("2")')
    .count();
cek('paginasi menawarkan halaman 2', totalDirektori > 12 && adaHal2 === 1, `${totalDirektori} resep`);

if (adaHal2) {
    await page.locator('nav[aria-label="Navigasi halaman"] a:text-is("2")').first().click();
    await tungguQuery(() => new URL(location.href).searchParams.get('page') === '2');

    cek('klik halaman 2 mengirim ?page=2', new URL(page.url()).searchParams.get('page') === '2', page.url());

    const kartuHal2 = await page.locator('main article').count();
    cek('halaman 2 tetap terisi kartu', kartuHal2 > 0, `${kartuHal2} kartu`);
}

// Pencarian tanpa hasil harus menjelaskan, bukan memperlihatkan layar kosong.
await page.goto(`${BASE}/resep?q=zzzzz`, { waitUntil: 'networkidle' });
const pesanKosong = await page.locator('main p.rounded-2xl').count();
const isiKosong = (await page.locator('main p.rounded-2xl').first().textContent())?.trim() ?? '';
cek(
    'pencarian tanpa hasil menampilkan pesan ramah',
    pesanKosong === 1 && isiKosong.includes('zzzzz'),
    isiKosong || '(tidak ada pesan)',
);

/* ── 11. Kartu resep membuka halaman detailnya sendiri ── */
await page.goto(`${BASE}/resep`, { waitUntil: 'networkidle' });

const judulKartu = (await page.locator('main article h3').first().textContent())?.trim() ?? '';
await page.locator('main article').first().click();
await tungguQuery(() => location.pathname.startsWith('/resep/'));

cek(
    'klik kartu menuju halaman detail resep',
    new URL(page.url()).pathname.startsWith('/resep/') && /\/resep\/[^/]+$/.test(new URL(page.url()).pathname),
    page.url(),
);

const judulDetail = (await page.locator('h1').first().textContent())?.trim() ?? '';
cek('judul detail sama dengan judul kartu', judulDetail === judulKartu, `${judulKartu} -> ${judulDetail}`);

// Nilai kolom gambar relatif terhadap root; di rute bersarang seperti ini
// kesalahan penulisan path akan berujung 404 yang tidak terlihat oleh
// pemeriksaan lain — makanya diperiksa langsung ke naturalWidth-nya.
const fotoDetail = await page
    .locator('main img')
    .first()
    .evaluate((img) => img.naturalWidth)
    .catch(() => 0);
cek('foto di halaman detail benar-benar termuat', fotoDetail > 0, `${fotoDetail}px`);

const bagianBahan = await page.locator('main section').filter({ hasText: 'Bahan' }).count();
cek('halaman detail memuat daftar bahan', bagianBahan >= 1, `${bagianBahan} bagian`);

const tautanKembali = await page.locator('main a', { hasText: 'Semua resep' }).count();
cek('halaman detail punya jalan kembali ke direktori', tautanKembali === 1, `${tautanKembali} tautan`);

// ── Daftar bahan bisa dicentang, dan tandanya bertahan setelah muat ulang ──
// Ini bagian yang benar-benar dipakai saat memasak, jadi tidak cukup hanya
// memastikan checkbox-nya ada.
const kotakBahan = page.locator('main ul input[type="checkbox"]');
const jumlahKotak = await kotakBahan.count();
cek('daftar bahan punya checkbox', jumlahKotak > 0, `${jumlahKotak} kotak`);

if (jumlahKotak > 0) {
    // Diklik lewat labelnya — checkbox-nya sendiri berukuran sr-only.
    await page.locator('main ul label').first().click();
    await page.waitForTimeout(200);

    const progres = (await page.locator('main [aria-live="polite"]').textContent())?.trim() ?? '';
    cek('centang bahan memperbarui progres', /^1 dari \d+ bahan/.test(progres), progres);

    const tercoret = await page.locator('main ul label span.line-through').count();
    cek('bahan yang dicentang tercoret', tercoret === 1, `${tercoret} baris`);

    const tersimpan = await page.evaluate(() =>
        Object.keys(localStorage).filter((k) => k.startsWith('kulinara:centang:')).length,
    );
    cek('tanda centang tersimpan di localStorage', tersimpan > 0, `${tersimpan} kunci`);

    await page.reload({ waitUntil: 'networkidle' });
    const setelahMuatUlang = (
        await page.locator('main [aria-live="polite"]').textContent()
    )?.trim();
    cek(
        'tanda centang bertahan setelah muat ulang',
        /^1 dari \d+ bahan/.test(setelahMuatUlang ?? ''),
        setelahMuatUlang ?? '(tidak ada)',
    );

    // Kembalikan ke keadaan bersih — run berikutnya dan tangkapan layar tidak
    // boleh mewarisi tanda centang dari run ini.
    await page.evaluate(() =>
        Object.keys(localStorage)
            .filter((k) => k.startsWith('kulinara:centang:'))
            .forEach((k) => localStorage.removeItem(k)),
    );
    await page.reload({ waitUntil: 'networkidle' });
}

// ── Blok resep lain sekategori ──
const blokTerkait = page.locator('main section').filter({ hasText: 'Resep lain' });
const jumlahBlok = await blokTerkait.count();
const jumlahTerkait = jumlahBlok > 0 ? await blokTerkait.first().locator('article').count() : 0;
cek(
    'halaman menawarkan resep lain sekategori',
    jumlahBlok === 1 && jumlahTerkait > 0 && jumlahTerkait <= 4,
    `${jumlahTerkait} kartu`,
);

/* ── 8. Halaman kredit foto — memenuhi syarat lisensi CC BY-SA ── */
await page.goto(`${BASE}/kredit-foto`, { waitUntil: 'networkidle' });

const judulKredit = (await page.locator('h1').first().textContent())?.trim();
cek('halaman kredit foto terbuka', judulKredit === 'Kredit Foto', judulKredit ?? '(tidak ada)');

const barisFoto = await page.locator('tbody tr').count();
cek('daftar kredit terisi', barisFoto > 0, `${barisFoto} baris`);

// Syarat CC BY-SA: setiap foto harus menautkan ke berkas sumbernya.
const tautanSumber = await page.locator('tbody tr a[target="_blank"]').count();
cek(
    'setiap baris punya tautan sumber',
    tautanSumber === barisFoto,
    `${tautanSumber}/${barisFoto}`,
);

const tanpaNoopener = await page
    .locator('tbody a[target="_blank"]:not([rel~="noopener"])')
    .count();
cek('tautan eksternal memakai noopener', tanpaNoopener === 0, `${tanpaNoopener} pelanggaran`);

// Saringan halaman kredit harus benar-benar menyaring, bukan hiasan.
if (barisFoto > 1) {
    await page.fill('#cari-foto', 'zzz-tidak-ada-manapun');
    await page.waitForTimeout(250);
    const kosong = await page.locator('tbody tr').count();
    cek('saringan kredit menampilkan pesan kosong', kosong === 1, `${kosong} baris`);

    const contohJudul = (await page.locator('tbody tr td').first().textContent())?.trim() ?? '';
    await page.fill('#cari-foto', contohJudul.split(/\s+/)[0] ?? '');
    await page.waitForTimeout(250);
    const tersaring = await page.locator('tbody tr').count();
    cek('saringan kredit menemukan hasil', tersaring > 0, `${tersaring} baris cocok`);
}

// Kredit foto harus terjangkau dari footer beranda tanpa perlu masuk akun.
await page.goto(`${BASE}/`, { waitUntil: 'networkidle' });
const tautanFooter = page.locator('footer a[href*="kredit-foto"]');
cek('footer menautkan ke kredit foto', (await tautanFooter.count()) === 1);

cek('tanpa error console', errors.length === 0, errors.join(' | ') || '0 error');

const gagal = hasil.filter((h) => !h.lolos);
console.log(`\n${hasil.length - gagal.length}/${hasil.length} lolos`);

await browser.close();
process.exit(gagal.length ? 1 : 0);
