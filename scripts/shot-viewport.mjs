import { chromium } from 'playwright';

/**
 * Cuplikan viewport (bukan full-page) untuk menilai bagian atas halaman:
 * apakah navbar benar-benar menyatu dengan hero.
 *
 * Pakai: node scripts/shot-viewport.mjs [url] [out.png] [lebar] [tinggi] [scrollY]
 * (Berkas inspeksi sementara — aman dihapus.)
 */

const a = process.argv.slice(2);
const url = a[0] ?? 'http://127.0.0.1:8000/';
const out = a[1] ?? 'screenshots/viewport-atas.png';
const lebar = Number.parseInt(a[2] ?? '1440', 10);
const tinggi = Number.parseInt(a[3] ?? '900', 10);
const gulir = Number.parseInt(a[4] ?? '0', 10);

const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: lebar, height: tinggi } });

const errors = [];
page.on('console', (m) => m.type() === 'error' && errors.push(m.text()));
page.on('pageerror', (e) => errors.push(`PAGEERROR: ${e.message}`));

await page.goto(url, { waitUntil: 'networkidle', timeout: 45000 });
if (gulir) await page.evaluate((y) => window.scrollTo(0, y), gulir);
await page.waitForTimeout(600);

await page.screenshot({ path: out, fullPage: false });

// Bukti ukuran: warna latar + jarak vertikal navbar ke hero.
const ukur = await page.evaluate(() => {
    const warna = (el) => (el ? getComputedStyle(el).backgroundColor : null);
    const header = document.querySelector('header');
    const hero = header?.nextElementSibling;

    return {
        header: warna(header),
        hero: warna(hero),
        border: getComputedStyle(header).borderBottomWidth,
        jarak: Math.round(
            (hero?.getBoundingClientRect().top ?? 0) -
                (header?.getBoundingClientRect().bottom ?? 0),
        ),
    };
});

console.log('output  :', out);
console.log('navbar  :', ukur.header, '| border-b:', ukur.border);
console.log('hero    :', ukur.hero);
console.log('jarak   :', `${ukur.jarak}px (0 = menyatu)`);
console.log('console :', errors.length ? errors : 'tanpa error');

await browser.close();
