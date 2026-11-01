/**
 * Uji alur Studio Dapur di browser sungguhan (Playwright).
 *
 * Pakai (server Laravel harus sudah jalan):
 *   node scripts/e2e-studio.mjs [url-asal]
 *
 * Yang diuji berurutan:
 *   1. Masuk sebagai chef demo
 *   2. Kirim form kosong — galat validasi harus muncul di halaman
 *   3. Isi form lengkap lalu simpan — harus redirect ke daftar + toast
 *   4. Resep baru harus terlihat di daftar
 *
 * Screenshot disimpan ke screenshots/.
 */
import { chromium } from 'playwright';

const asal = process.argv[2] ?? 'http://127.0.0.1:8000';
const EMAIL = 'chef@kulinara.test';
const SANDI = 'password';

const log = (label, isi) => console.log(label.padEnd(14) + ': ' + isi);

const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: 1440, height: 900 } });

const errors = [];
page.on('console', (m) => m.type() === 'error' && errors.push(m.text()));
page.on('pageerror', (e) => errors.push('PAGEERROR: ' + e.message));

const hapusRespons = [];
page.on('response', (r) => {
    if (r.request().method() === 'DELETE') hapusRespons.push(r.status() + ' ' + r.url());
});

try {
    // 1. Masuk
    await page.goto(`${asal}/login`, { waitUntil: 'networkidle', timeout: 45000 });
    await page.fill('input[name="email"]', EMAIL);
    await page.fill('input[name="password"]', SANDI);
    await page.keyboard.press('Enter');
    await page.waitForURL((u) => !u.pathname.startsWith('/login'), { timeout: 20000 });
    log('masuk', `OK sebagai ${EMAIL}`);

    // 2. Form kosong → galat validasi
    await page.goto(`${asal}/studio-dapur/buat`, { waitUntil: 'networkidle', timeout: 45000 });
    await page.fill('#judul', 'Tes');
    await page.click('button[type="submit"]');

    // Tunggu satu pesan galat dulu, baru kumpulkan semuanya.
    const galatPertama = page.getByText('Judul resep minimal 5 karakter.');
    await galatPertama.waitFor({ timeout: 20000 });

    const galat = await page.locator('[role="alert"]').allTextContents();
    log('galat form', `${galat.length} pesan muncul`);
    galat.forEach((g) => console.log('  • ' + g));
    await page.screenshot({ path: 'screenshots/studio-validasi.png', fullPage: true });

    // 3. Isi lengkap lalu simpan
    const judul = `Pepes Tahu Kemangi ${Date.now() % 100000}`;

    await page.fill('#judul', judul);
    await page.fill(
        '#deskripsi',
        'Pepes tahu lembut dengan kemangi segar, dikukus dalam daun pisang tanpa minyak berlebih.',
    );
    await page.selectOption('#kategori', 'sayur_nabati');
    await page.fill('#daerah', 'Jawa Barat');
    await page.selectOption('#kesulitan', 'mudah');
    await page.fill('#waktu', '45');
    await page.fill('#porsi', '3');

    await page.fill('#gizi-energi_kkal', '210');
    await page.fill('#gizi-protein_g', '9.5');
    await page.fill('#gizi-lemak_g', '7.2');
    await page.fill('#gizi-karbohidrat_g', '22');

    await page.fill('#bahan-nama-0', 'Tahu putih');
    await page.fill('#bahan-jumlah-0', '300');
    await page.selectOption('#bahan-satuan-0', 'gram');
    await page.click('button:has-text("Tambah bahan")');
    await page.waitForTimeout(200);
    await page.fill('#bahan-nama-1', 'Daun kemangi');
    await page.fill('#bahan-jumlah-1', '2');
    await page.selectOption('#bahan-satuan-1', 'ikat');

    await page.fill(
        '#langkah-0',
        'Hancurkan tahu, campur kemangi dan bumbu halus sampai rata.',
    );
    await page.click('button:has-text("Tambah langkah")');
    await page.waitForTimeout(200);
    await page.fill(
        '#langkah-1',
        'Bungkus adonan dengan daun pisang lalu kukus selama 25 menit.',
    );

    // Input radio-nya sr-only dan tertimpa labelnya, jadi klik labelnya.
    await page.locator('label:has(input[name="status"][value="terbit"])').click();

    await page.screenshot({ path: 'screenshots/studio-isi.png', fullPage: true });

    await page.click('button[type="submit"]');
    await page.waitForURL((u) => u.pathname === '/studio-dapur', { timeout: 20000 });
    await page.waitForTimeout(1200);

    // 4. Cek daftar + toast
    const adaJudul = await page.getByText(judul, { exact: false }).first().isVisible();
    const toast = await page
        .locator('text=Resep berhasil disimpan')
        .first()
        .isVisible()
        .catch(() => false);

    log('redirect', 'OK ke /studio-dapur');
    log('resep tampil', adaJudul ? 'YA' : 'TIDAK');
    log('toast', toast ? 'muncul' : 'tidak terlihat (mungkin sudah kedip)');

    await page.screenshot({ path: 'screenshots/studio-list.png', fullPage: true });

    // 5. Ubah — form harus terisi data lama, lalu simpan perubahan
    await page.locator('button:has-text("Ubah")').first().click();
    await page.waitForURL((u) => u.pathname.endsWith('/edit'), { timeout: 20000 });
    await page.waitForTimeout(1000);

    const judulEdit = await page.inputValue('#judul');
    log('form ubah', judulEdit === judul ? 'terisi data lama' : `SALAH (${judulEdit})`);
    await page.screenshot({ path: 'screenshots/studio-edit.png', fullPage: true });

    const judulBaru = `${judul} Disunting`;
    await page.fill('#judul', judulBaru);
    await page.click('button[type="submit"]');
    await page.waitForURL((u) => u.pathname === '/studio-dapur', { timeout: 20000 });
    await page.waitForTimeout(1200);

    const tersimpan = await page
        .getByText(judulBaru, { exact: false })
        .first()
        .isVisible();
    log('ubah tersimpan', tersimpan ? 'YA' : 'TIDAK');

    // 6. Hapus — modal konfirmasi harus muncul dulu
    await page.locator('button:has-text("Hapus")').first().click();
    await page.getByText('Hapus resep ini?').waitFor({ timeout: 10000 });
    await page.screenshot({ path: 'screenshots/studio-modal-hapus.png' });

    await page.locator('button:has-text("Ya, Hapus")').click();

    // Tunggu barisnya benar-benar hilang — waitForURL tidak berguna di sini
    // karena kita sudah berada di /studio-dapur.
    const hilang = await page
        .getByText(judulBaru, { exact: false })
        .first()
        .waitFor({ state: 'detached', timeout: 20000 })
        .then(() => true)
        .catch(() => false);

    log('respons hapus', hapusRespons.join(' | ') || 'TIDAK ADA PERMINTAAN');
    log('dihapus', hilang ? 'YA — resep hilang dari daftar' : 'TIDAK');
    await page.screenshot({ path: 'screenshots/studio-setelah-hapus.png', fullPage: true });
} catch (err) {
    log('GAGAL', err.message);
    await page.screenshot({ path: 'screenshots/studio-e2e-gagal.png', fullPage: true }).catch(() => {});
    process.exitCode = 1;
} finally {
    log('console', errors.length === 0 ? 'tanpa error' : `${errors.length} error`);
    errors.forEach((e) => console.log('  ! ' + e));
    await browser.close();
}
