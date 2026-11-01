import { mkdir, readdir, readFile, rm, stat, writeFile } from 'node:fs/promises';

/**
 * Unduh foto hasil panen ke public/images/resep/<slug>.jpg.
 *
 * Menulis juga database/seeders/data/foto.json sebagai manifest kredit:
 * penulis, lisensi, dan tautan sumber tiap foto. Manifest ini dipakai halaman
 * /kredit-foto, yang wajib ada karena mayoritas foto berlisensi CC BY-SA dan
 * syarat lisensinya mewajibkan mencantumkan kredit.
 *
 * Idempoten: file yang sudah terunduh dilewati, jadi script boleh dijalankan
 * ulang kapan saja — misal setelah koneksi terputus di tengah jalan.
 *
 * Hidangan yang gagal terunduh Dibuang dari hidangan.json, sehingga cakupan
 * foto dijamin 100% dan tidak ada resep yang tampil dengan kotak kosong.
 *
 * Pakai:
 *   node scripts/unduh-foto.mjs
 */

const UA = 'KulinaraSeeder/1.0 (dataset contoh lokal; hubungi dev@kulinara.test)';
const JEDA_MS = 200;

const DATA = 'database/seeders/data/hidangan.json';
const MANIFEST = 'database/seeders/data/foto.json';
const TUJUAN = 'public/images/resep';

const tunggu = (ms) => new Promise((r) => setTimeout(r, ms));

async function unduh(url) {
    for (let coba = 1; coba <= 4; coba++) {
        const res = await fetch(url, { headers: { 'User-Agent': UA } });

        if (res.status === 429 || res.status >= 500) {
            await tunggu(1500 * coba);
            continue;
        }

        if (!res.ok) {
            throw new Error(`HTTP ${res.status}`);
        }

        return Buffer.from(await res.arrayBuffer());
    }

    throw new Error('Menyerah setelah 4 kali percobaan.');
}

let hidangan;
try {
    hidangan = JSON.parse(await readFile(DATA, 'utf8'));
} catch {
    console.error(
        `Tidak menemukan ${DATA}.\nJalankan dulu: node scripts/panen-hidangan.mjs`,
    );
    process.exit(1);
}

await mkdir(TUJUAN, { recursive: true });

// Buang berkas yatim SEBELUM mengunduh, bukan sesudahnya. Bila tidak, foto
// lama milik hidangan yang sudah keluar dari dataset bisa terpakai ulang oleh
// slug baru yang kebetulan sama — dan karena unduhan melewati berkas yang
// sudah ada, kesalahan itu tidak akan pernah terkoreksi sendiri.
const slugDiharapkan = new Set(hidangan.map((h) => `${h.slug}.jpg`));
let dibuang = 0;

for (const berkas of await readdir(TUJUAN).catch(() => [])) {
    if (berkas.endsWith('.jpg') && !slugDiharapkan.has(berkas)) {
        await rm(`${TUJUAN}/${berkas}`);
        dibuang++;
    }
}

if (dibuang > 0) {
    console.log(`berkas yatim dibuang: ${dibuang}`);
}

const manifest = [];
const gagal = [];
let totalBait = 0;
let baru = 0;

for (const [i, h] of hidangan.entries()) {
    const berkas = `${TUJUAN}/${h.slug}.jpg`;
    const tujuan = `images/resep/${h.slug}.jpg`;

    try {
        const sudahAda = await stat(berkas).catch(() => null);

        if (sudahAda && sudahAda.size > 0) {
            totalBait += sudahAda.size;
        } else {
            const isi = await unduh(h.foto.url);
            if (isi.length < 5000) {
                throw new Error(`terlalu kecil (${isi.length} bait)`);
            }
            await writeFile(berkas, isi);
            totalBait += isi.length;
            baru++;
            await tunggu(JEDA_MS);
        }

        manifest.push({
            slug: h.slug,
            judul: h.judul,
            berkas: tujuan,
            penulis: h.foto.penulis,
            lisensi: h.foto.lisensi,
            halaman: h.foto.halaman,
            berkas_sumber: h.foto.nama_berkas,
        });
    } catch (e) {
        gagal.push({ judul: h.judul, alasan: e.message });
    }

    process.stdout.write(
        `\r  mengunduh… ${i + 1}/${hidangan.length} | ${manifest.length} berhasil | ${gagal.length} gagal`,
    );
}

process.stdout.write('\n\n');

// Sisihkan yang gagal dari dataset utama — kalau dibiarkan, resepnya akan
// tampil tanpa foto dan merusak klaim "foto wajib ada".
const tersisa = hidangan.filter((h) => manifest.some((m) => m.slug === h.slug));

await writeFile(DATA, JSON.stringify(tersisa, null, 2), 'utf8');
await writeFile(MANIFEST, JSON.stringify(manifest, null, 2), 'utf8');

const lisensi = {};
for (const m of manifest) {
    const l = m.lisensi || '(tidak tercatat)';
    lisensi[l] = (lisensi[l] || 0) + 1;
}

console.log(`terunduh : ${baru} baru, ${manifest.length - baru} sudah ada sebelumnya`);
console.log(`gagal    : ${gagal.length}`);
for (const g of gagal.slice(0, 10)) {
    console.log(`   - ${g.judul}: ${g.alasan}`);
}
console.log(`dataset  : ${tersisa.length} hidangan berfoto di ${DATA}`);
console.log(`manifest : ${MANIFEST}`);
console.log(`ukuran   : ${(totalBait / 1024 / 1024).toFixed(1)} MB di ${TUJUAN}`);
console.log('lisensi  :', lisensi);
