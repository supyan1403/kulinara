import { mkdir, readFile, writeFile } from 'node:fs/promises';
import {
    bersih,
    daerahDari,
    judulBersih,
    klasifikasi,
    layakJadiKandidat,
    pilihFoto,
    slugify,
    JANGAN_TURUN,
    KATA_GENERIK,
} from './panen-filter.mjs';

/**
 * Panen nama hidangan Indonesia yang PUNYA FOTO dari Wikimedia Commons.
 *
 * Keputusan penting: kita membalik urutannya. Alih-alih membuat ratusan resep
 * lalu berburu foto untuk semuanya (yang hasilnya pasti bolong), kita ambil
 * dulu hidangan yang fotonya memang tersedia, lalu menulis resep untuk
 * hidangan-hidangan itu. Cakupan fotonya dijamin 100% dan nama resep tidak
 * pernah dipaksa cocok dengan foto milik masakan lain.
 *
 * Tiga pelajaran dari percobaan pertama, semuanya sudah tercermin di sini:
 *
 *   1. Kategori di Commons ada dua jenis. Kategori TOPIK ("Meals in
 *      Indonesia", "WikiSambal", "Slaughterhouses in Surabaya") berisi
 *      dokumentasi kegiatan, bukan satu hidangan. Kategori ini harus dibuang
 *      sebagai kandidat resep.
 *   2. Namun kategori topik itu sering mengandung hidangan asli sebagai
 *      anaknya. Karena itu filter kandidat dan filter turun-kategori dipisah:
 *      menolak kandidat tidak berarti berhenti menelusuri.
 *   3. Panggilan ke Wikipedia bisa dihemat 50 kali lipat dengan menyusun judul
 *      bertanda pipa (titles=A|B|C). Dari ~300 permintaan jadi 6.
 *
 * Saringan dan klasifikasi ada di ./panen-filter.mjs supaya bisa diuji tanpa
 * jaringan (node scripts/uji-saringan.mjs).
 *
 * Pakai:
 *   node scripts/panen-hidangan.mjs
 */

const API = 'https://commons.wikimedia.org/w/api.php';
const WIKI = 'https://id.wikipedia.org/w/api.php';
const UA = 'KulinaraSeeder/1.0 (dataset contoh lokal; hubungi dev@kulinara.test)';

const AKAR = 'Category:Cuisine of Indonesia';
const KEDALAMAN_MAKS = 5;
const JEDA_MS = 400;
// Sedikit di atas target akhir. Kategori yang berbagi satu berkas foto harus
// dilepas (dua resep dengan piring identik terlihat hasil generate), jadi
// sebagian kandidat memang harus dibuang.
const TARGET = 380;

const KELUAR = 'database/seeders/data';
const CACHE_KATEGORI = `${KELUAR}/_cache-kategori.json`;
const CACHE_FILE = `${KELUAR}/_cache-file.json`;
const HASIL = `${KELUAR}/hidangan.json`;

/* ── HTTP ───────────────────────────────────────────────────────────────── */

const tunggu = (ms) => new Promise((r) => setTimeout(r, ms));

let permintaan = 0;

async function api(url, params) {
    for (let coba = 1; coba <= 5; coba++) {
        permintaan++;
        const penuh = `${url}?${new URLSearchParams({ ...params, format: 'json', formatversion: '2' })}`;
        const res = await fetch(penuh, { headers: { 'User-Agent': UA } });

        if (res.status === 429) {
            const jeda = 3000 * coba;
            process.stdout.write(`\n  ! 429, menunggu ${jeda / 1000}s (${coba}/5)`);
            await tunggu(jeda);
            continue;
        }

        if (!res.ok) {
            throw new Error(`HTTP ${res.status} untuk ${penuh}`);
        }

        const teks = await res.text();
        try {
            return JSON.parse(teks);
        } catch {
            throw new Error(`Respons bukan JSON: ${teks.slice(0, 150)}`);
        }
    }

    throw new Error('Menyerah setelah 5 kali kena 429.');
}

/** Anak kategori sekaligus file dalam satu permintaan. */
async function anggota(kategori) {
    const j = await api(API, {
        action: 'query',
        list: 'categorymembers',
        cmtitle: kategori,
        cmtype: 'subcat|file',
        cmlimit: '200',
    });
    await tunggu(JEDA_MS);

    const daftar = (j.query && j.query.categorymembers) || [];

    return {
        subkategori: daftar.filter((a) => a.ns === 14).map((a) => a.title),
        file: daftar.filter((a) => a.ns === 6).map((a) => a.title),
    };
}

/** Info file: url thumbnail 900px, lisensi, dan penulis untuk kredit. */
async function infoFile(kategori) {
    const j = await api(API, {
        action: 'query',
        generator: 'categorymembers',
        gcmtitle: kategori,
        gcmtype: 'file',
        gcmlimit: '40',
        prop: 'imageinfo',
        iiprop: 'url|extmetadata|size',
        iiurlwidth: '900',
    });
    await tunggu(JEDA_MS);

    return ((j.query && j.query.pages) || []).map((p) => {
        const ii = (p.imageinfo && p.imageinfo[0]) || {};
        const meta = ii.extmetadata || {};

        return {
            nama: p.title.replace(/^File:/, ''),
            thumb: ii.thumburl || ii.url || null,
            lebar: ii.thumbwidth || 0,
            tinggi: ii.thumbheight || 0,
            // Lebar berkas ASLI, bukan thumbnail. Thumbnail selalu 900px karena
            // kita memintanya demikian, sehingga tidak bisa dipakai menilai
            // apakah sumbernya cukup tajam untuk dirender sebesar itu.
            lebar_asli: ii.width || 0,
            lisensi: (meta.LicenseShortName && meta.LicenseShortName.value) || null,
            penulis: bersih(meta.Artist && meta.Artist.value),
            halaman: `https://commons.wikimedia.org/wiki/${encodeURIComponent(p.title)}`,
        };
    });
}

/**
 * Potong extract menjadi dua kalimat pertama yang muat di kartu resep.
 *
 * Versi lama menolak extract yang melebihi 420 karakter. Itu membuang artikel
 * besar seperti "Nasi goreng" dan "Tumpeng" — justru yang paling ingin kita
 * punya deskripsinya. Memotong jauh lebih baik daripada menolak.
 */
function duaKalimat(teks) {
    let hasil = teks.split(/(?<=\.)\s+/).slice(0, 2).join(' ');

    if (hasil.length > 420) {
        hasil = hasil.slice(0, 420).replace(/\s+\S*$/, '');
    }

    return hasil;
}

/**
 * Jumlah judul yang boleh dikirim sekali jalan ke API `prop=extracts`.
 *
 * TIDAK 50. Meski `titles` menerima 50 judul, `exlimit` membatasi extract
 * yang dikembalikan per permintaan — `exlimit=max` pun hanya 20. Mem-batch
 * 50 judul membuat 30 dari 50 extract dibuang diam-diam, dan itu persis
 * gejala yang kita lihat: 300 hidangan tapi deskripsi asli mentok di ~130.
 *
 * Angka ini harus selalu = batas exlimit, bukan batas titles.
 */
const PER_BACH = 20;

/**
 * Ambil ringkasan Wikipedia Indonesia untuk banyak judul sekaligus.
 *
 * Menyusun judul bertanda pipa memangkas ratusan panggilan jadi belasan, yang
 * sekaligus menurunkan risiko kena 429.
 *
 * @returns {Promise<Record<string, string>>} judul -> ringkasan dua kalimat
 */
async function ringkasanMassal(judul) {
    const hasil = {};

    for (let i = 0; i < judul.length; i += PER_BACH) {
        const batch = judul.slice(i, i + PER_BACH);

        const j = await api(WIKI, {
            action: 'query',
            prop: 'extracts',
            exintro: '1',
            explaintext: '1',
            exlimit: 'max',
            redirects: '1',
            titles: batch.join('|'),
        });
        await tunggu(JEDA_MS);

        // Pada putaran pertama judul yang ditanya persis sama dengan artikelnya,
        // jadi kunci simpannya ya judul itu sendiri.
        isiKeHasil(j.query || {}, hasil, (asal) => asal);

        process.stdout.write(
            `\r  memungut ringkasan… ${Math.min(i + PER_BACH, judul.length)}/${judul.length}`,
        );
    }

    // Cadangan: "Pempek Keriting" tidak punya artikel sendiri, tetapi
    // "Pempek" ada dan deskripsinya tetap relevan. Kita mencoba seluruh
    // awalan judul ("Nasi goreng kambing" -> "Nasi goreng", lalu "Nasi"),
    // lalu menerima hasilnya hanya bila artikelnya benar-benar membuka dengan
    // awalan itu — tanpa syarat ini, "Bubur randang" akan salah mendapat
    // artikel "Bubur" yang membahas teknik merebus, bukan resepnya.
    const kosong = judul.filter((j) => !hasil[j] && j.trim().split(/\s+/).length > 1);

    if (kosong.length > 0) {
        // awalan -> hidangan yang memakainya. Beberapa hidangan bisa berbagi
        // satu awalan ("Rujak cingur" dan "Rujak buah" sama-sama punya
        // awalan "Rujak"); yang pertama berhak memakainya.
        const pemilik = new Map();

        for (const judulKosong of kosong) {
            const kata = judulKosong.trim().split(/\s+/);

            for (let potong = 1; potong < kata.length; potong++) {
                const awalan = kata.slice(0, kata.length - potong).join(' ');
                if (awalan && !pemilik.has(awalan)) {
                    pemilik.set(awalan, judulKosong);
                }
            }
        }

        const daftarAwalan = [...pemilik.keys()];

        for (let i = 0; i < daftarAwalan.length; i += PER_BACH) {
            const batch = daftarAwalan.slice(i, i + PER_BACH);

            const j = await api(WIKI, {
                action: 'query',
                prop: 'extracts',
                exintro: '1',
                explaintext: '1',
                exlimit: 'max',
                redirects: '1',
                titles: batch.join('|'),
            });
            await tunggu(JEDA_MS);

            isiKeHasil(j.query || {}, hasil, (asal, judulArtikel) => {
                const awalan = String(asal).toLowerCase();
                const artikel = String(judulArtikel).toLowerCase();

                const cocok =
                    artikel === awalan ||
                    artikel.startsWith(`${awalan} `) ||
                    awalan.startsWith(`${artikel} `);

                if (!cocok || KATA_GENERIK.has(awalan)) {
                    return null;
                }

                // Disimpan di bawah judul hidangan aslinya, bukan di bawah
                // awalan — pembaca nanti selalu meminta ringkasan[judul].
                return pemilik.get(asal) ?? null;
            });

            process.stdout.write(
                `\r  memungut ringkasan awalan… ${Math.min(i + PER_BACH, daftarAwalan.length)}/${daftarAwalan.length}`,
            );
        }
    }

    process.stdout.write('\n');

    return hasil;
}

/**
 * Salin extract dari respons Wikipedia ke peta hasil.
 *
 * @param {{pages?: Array<{title: string, extract?: string}>, redirects?: Array<{from: string, to: string}>, normalized?: Array<{from: string, to: string}>}} isi
 * @param {Record<string, string>} hasil
 * @param {(judulYangDitanya: string, judulArtikel: string) => (string|null)} peta
 *        Mengembalikan kunci tempat extract disimpan, atau null untuk lewat.
 */
function isiKeHasil(isi, hasil, peta) {
    const balik = {};
    for (const t of [...(isi.redirects || []), ...(isi.normalized || [])]) {
        balik[String(t.to).toLowerCase()] = t.from;
    }

    for (const p of isi.pages || []) {
        if (!p.extract) {
            continue;
        }

        const asal = balik[String(p.title).toLowerCase()] || p.title;
        const kunci = peta(asal, p.title);

        if (!kunci || hasil[kunci]) {
            continue;
        }

        const ringkas = duaKalimat(p.extract);

        if (ringkas.length >= 40) {
            hasil[kunci] = ringkas;
        }
    }
}

/**
 * Ambil kategori artikel Wikipedia untuk banyak judul sekaligus.
 *
 * Inilah sumber daerah yang paling bisa dipercaya. Artikel "Gudeg" masuk
 * kategori "Hidangan Yogyakarta", artikel "Pempek" masuk "Hidangan
 * Palembang" — disusun oleh penyunting manusia. Jalur kategori Commons
 * sebaliknya sering hanya "Cuisine of Indonesia", sehingga daerahnya tidak
 * pernah ketahuan. Tanpa langkah ini sekitar 9 dari 10 resep berakhir tanpa
 * daerah.
 *
 * @returns {Promise<Record<string, string[]>>} judul -> nama-nama kategori
 */
async function kategoriArtikel(judul) {
    const peta = {};

    for (let i = 0; i < judul.length; i += PER_BACH) {
        const batch = judul.slice(i, i + PER_BACH);

        const j = await api(WIKI, {
            action: 'query',
            prop: 'categories',
            cllimit: 'max',
            clshow: '!hidden',
            redirects: '1',
            titles: batch.join('|'),
        });
        await tunggu(JEDA_MS);

        const isi = j.query || {};

        const balik = {};
        for (const t of [...(isi.redirects || []), ...(isi.normalized || [])]) {
            balik[String(t.to).toLowerCase()] = t.from;
        }

        for (const p of isi.pages || []) {
            const asal = balik[String(p.title).toLowerCase()] || p.title;
            peta[asal] = (p.categories || []).map((c) =>
                String(c.title).replace(/^Kategori:/, ''),
            );
        }

        process.stdout.write(
            `\r  memungut kategori artikel… ${Math.min(i + PER_BACH, judul.length)}/${judul.length}`,
        );
    }

    process.stdout.write('\n');

    return peta;
}

/* ── Tahap 1: telusuri pohon kategori ───────────────────────────────────── */

let cacheKategori = {};
let cacheFile = {};

async function telusuri() {
    console.log(`\nakar   : ${AKAR} (kedalaman maks ${KEDALAMAN_MAKS})`);

    const antrean = [{ kategori: AKAR, jalur: [AKAR], kedalaman: 0 }];
    const sudah = new Set();
    const kandidat = [];

    while (antrean.length > 0) {
        const sekarang = antrean.shift();
        if (sudah.has(sekarang.kategori) || sekarang.kedalaman > KEDALAMAN_MAKS) {
            continue;
        }
        sudah.add(sekarang.kategori);

        const isi = cacheKategori[sekarang.kategori] ?? (await anggota(sekarang.kategori));
        cacheKategori[sekarang.kategori] = isi;

        // Pemisahan krusial: kategori topik tidak jadi kandidat, tetapi anaknya
        // tetap ditelusuri. Tanpa ini "Vermicelli dishes of Indonesia" memutus
        // seluruh keturunannya yang berisi hidangan sungguhan.
        if (
            sekarang.kedalaman >= 1 &&
            isi.file.length > 0 &&
            layakJadiKandidat(sekarang.kategori)
        ) {
            kandidat.push({ kategori: sekarang.kategori, jalur: sekarang.jalur });
        }

        for (const anak of isi.subkategori) {
            if (!sudah.has(anak) && !JANGAN_TURUN.test(anak)) {
                antrean.push({
                    kategori: anak,
                    jalur: [...sekarang.jalur, anak],
                    kedalaman: sekarang.kedalaman + 1,
                });
            }
        }

        process.stdout.write(`\r  menelusuri… ${sudah.size} kategori, ${kandidat.length} kandidat`);
    }

    process.stdout.write('\n');

    return kandidat;
}

/* ── Jalankan ───────────────────────────────────────────────────────────── */

await mkdir(KELUAR, { recursive: true });

try {
    cacheKategori = JSON.parse(await readFile(CACHE_KATEGORI, 'utf8'));
    console.log(`cache  : ${Object.keys(cacheKategori).length} kategori`);
} catch {
    cacheKategori = {};
}

try {
    cacheFile = JSON.parse(await readFile(CACHE_FILE, 'utf8'));
    console.log(`cache  : ${Object.keys(cacheFile).length} info file`);
} catch {
    cacheFile = {};
}

const kandidat = await telusuri();

await writeFile(CACHE_KATEGORI, JSON.stringify(cacheKategori), 'utf8');

// Buang duplikat berdasarkan judul — kategori yang sama sering muncul dari dua
// jalur berbeda (mis. "Category:Rendang" dan "Cuisine of Minangkabau").
const unik = new Map();
for (const k of kandidat) {
    const kunci = judulBersih(k.kategori).toLowerCase();
    if (!unik.has(kunci)) {
        unik.set(kunci, k);
    }
}

console.log(`\nkandidat unik: ${unik.size}`);

const terpilih = [];
const fotoTerpakai = new Set();
let dihitung = 0;

for (const { kategori, jalur } of unik.values()) {
    dihitung++;
    const judul = judulBersih(kategori);

    try {
        const daftar = cacheFile[kategori] ?? (await infoFile(kategori));
        cacheFile[kategori] = daftar;

        const foto = pilihFoto(daftar, fotoTerpakai);
        if (foto) {
            fotoTerpakai.add(foto.nama);
            terpilih.push({ judul, kategori, jalur, foto });
        }
    } catch (e) {
        process.stdout.write(`\n  ! gagal ${judul}: ${e.message}`);
    }

    process.stdout.write(`\r  memilih foto… ${dihitung}/${unik.size} | ${terpilih.length} berfoto`);

    if (terpilih.length >= TARGET) {
        break;
    }
}

process.stdout.write('\n');

await writeFile(CACHE_FILE, JSON.stringify(cacheFile), 'utf8');

// Hidangan dengan foto unggul sudah terkumpul. Sekarang satu batch besar ke
// Wikipedia untuk deskripsi yang asli, bukan kalimat buatan.
const ringkasan = await ringkasanMassal(terpilih.map((t) => t.judul));

// Satu batch lagi untuk daerah asal, dari kategori artikel Wikipedia.
const artikelPerJudul = await kategoriArtikel(terpilih.map((t) => t.judul));

const hasil = [];
const slugPakai = new Set();

for (const { judul, jalur, foto } of terpilih) {
    const slug = slugify(judul);
    if (!slug || slugPakai.has(slug)) {
        continue;
    }
    slugPakai.add(slug);

    hasil.push({
        judul,
        slug,
        kategori: klasifikasi(judul),
        daerah: daerahDari(jalur, judul, artikelPerJudul[judul] ?? []),
        deskripsi: ringkasan[judul] ?? null,
        foto: {
            url: foto.thumb,
            nama_berkas: foto.nama,
            penulis: foto.penulis,
            lisensi: foto.lisensi,
            halaman: foto.halaman,
        },
    });
}

await writeFile(HASIL, JSON.stringify(hasil, null, 2), 'utf8');

const hitung = {};
const hitungDaerah = {};
for (const h of hasil) {
    hitung[h.kategori] = (hitung[h.kategori] || 0) + 1;
    const d = h.daerah || '(tanpa daerah)';
    hitungDaerah[d] = (hitungDaerah[d] || 0) + 1;
}

console.log(`\nselesai: ${hasil.length} hidangan -> ${HASIL}`);
console.log('per kategori :', hitung);
console.log(
    'per daerah   :',
    Object.fromEntries(Object.entries(hitungDaerah).sort((a, b) => b[1] - a[1]).slice(0, 12)),
);
console.log(`deskripsi asli: ${hasil.filter((h) => h.deskripsi).length} dari ${hasil.length}`);
console.log(`total permintaan API: ${permintaan}`);
