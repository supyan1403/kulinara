import { chromium } from 'playwright';
import { mkdirSync } from 'node:fs';
import { dirname } from 'node:path';

/**
 * Screenshot halaman untuk verifikasi visual.
 *
 * Pakai:
 *   node scripts/screenshot.mjs <url> <output.png> [lebar] [--login=email] [--password=sandi]
 *
 * Contoh:
 *   node scripts/screenshot.mjs http://127.0.0.1:8000/ screenshots/landing-desktop.png 1440
 *   node scripts/screenshot.mjs http://127.0.0.1:8000/studio-dapur screenshots/studio.png 1440 --login=chef@kulinara.test
 *
 * Screenshot diambil full-page, dan error console ikut dilaporkan supaya
 * ketahuan kalau ada JavaScript yang gagal diam-diam.
 */

const posisi = process.argv.slice(2).filter((a) => !a.startsWith('--'));

const url = posisi[0] ?? 'http://127.0.0.1:8000/';
const out = posisi[1] ?? 'screenshots/page.png';
const width = Number.parseInt(posisi[2] ?? '1440', 10);

const opsi = (nama, bawaan = null) => {
    const hit = process.argv.find((a) => a.startsWith(`--${nama}=`));
    return hit ? hit.slice(nama.length + 3) : bawaan;
};

const email = opsi('login');
const kataSandi = opsi('password', 'password');

mkdirSync(dirname(out), { recursive: true });

const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width, height: 900 } });

const errors = [];
page.on('console', (msg) => {
    if (msg.type() === 'error') errors.push(msg.text());
});
page.on('pageerror', (err) => errors.push(`PAGEERROR: ${err.message}`));
page.on('requestfailed', (req) =>
    errors.push(`REQUEST FAILED: ${req.url()} — ${req.failure()?.errorText}`),
);

if (email) {
    const asal = new URL(url).origin;

    await page.goto(`${asal}/login`, { waitUntil: 'networkidle', timeout: 45000 });
    await page.fill('input[name="email"]', email);
    await page.fill('input[name="password"]', kataSandi);
    await page.keyboard.press('Enter');

    const berhasil = await page
        .waitForURL((u) => !u.pathname.startsWith('/login'), { timeout: 20000 })
        .then(() => true)
        .catch(() => false);

    console.log('login    :', berhasil ? `OK sebagai ${email}` : 'GAGAL');
}

await page.goto(url, { waitUntil: 'networkidle', timeout: 45000 });
await page.waitForTimeout(1000); // beri waktu React merender + gambar termuat

// Gulir seluruh halaman dulu: komponen <Reveal/> baru muncul ketika masuk
// viewport, sedangkan mode fullPage membidik halaman sekaligus. Tanpa gulir
// ini, rekaman visual justru memperlihatkan konten yang belum sempat animasi.
await page.evaluate(async () => {
    const tinggi = document.body.scrollHeight;
    for (let y = 0; y <= tinggi; y += 400) {
        window.scrollTo(0, y);
        await new Promise((r) => setTimeout(r, 60));
    }
});
// Beri IntersectionObserver waktu mencatat elemen terbawah sebelum halaman
// digulir balik ke atas — kalau dibalik terlalu cepat, elemen bawah tidak
// sempat tercatat intersecting dan tetap transparan.
await page.waitForTimeout(800);
await page.evaluate(() => window.scrollTo(0, 0));
await page.waitForTimeout(900); // tunggu animasi terakhir selesai

const takTerlihat = await page.locator('[data-reveal].opacity-0').count();

await page.screenshot({ path: out, fullPage: true });

const box = await page.evaluate(() => ({
    w: document.documentElement.scrollWidth,
    h: document.documentElement.scrollHeight,
    text: document.body.innerText.length,
    imgs: [...document.images].map((i) => ({
        src: i.currentSrc.split('/').pop(),
        ok: i.complete && i.naturalWidth > 0,
    })),
    font: getComputedStyle(document.body).fontFamily,
    bg: getComputedStyle(document.body).backgroundColor,
}));

console.log('url      :', page.url());
console.log('title    :', await page.title());
console.log('output   :', out);
console.log('halaman  :', `${box.w}x${box.h} px, ${box.text} karakter teks`);
console.log('font     :', box.font);
console.log('bg       :', box.bg);
console.log('reveal   :', takTerlihat === 0 ? 'semua tampil' : `GAGAL ${takTerlihat} elemen masih transparan`);
console.log(
    'gambar   :',
    box.imgs.map((i) => `${i.ok ? 'OK' : 'GAGAL'} ${i.src}`).join(' | ') || '(tidak ada)',
);
console.log('console  :', errors.length ? errors : 'tanpa error');

await browser.close();
