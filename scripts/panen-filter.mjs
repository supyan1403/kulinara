/**
 * Aturan saringan dan klasifikasi untuk panen hidangan Wikimedia Commons.
 *
 * Dipisah dari script panen agar bisa diuji tanpa menyentuh jaringan:
 *   node scripts/uji-saringan.mjs
 */

/* ── Saringan foto ──────────────────────────────────────────────────────── */

// Nama file yang menandai foto tidak layak tampil di halaman resep.
export const FILE_DILARANG =
    /(indomie|indom|frozen|instant|instan|packag|logo|noodle|7-eleven|circle k|alfamart|indomaret|minimarket|advertis|poster|label|nutrition|diagram|chart|flag|coat of arms|seal of|banner|screenshot|barcode|receipt|calorie)/i;

export const EKSTENSI_BAIK = /\.(jpe?g|png)$/i;

/**
 * Pilih foto terbaik: berkas asli yang besar dan lolos saringan nama file.
 *
 * @param {Array} daftar          info file dari satu kategori Commons
 * @param {Set<string>} dipakai   nama berkas yang sudah dipakai hidangan lain
 * @returns {object|null}
 *
 * Foto yang sudah dipakai hidangan lain tidak boleh dipakai ulang: dua resep
 * berbeda menampilkan piring yang sama persis adalah tanda paling jelas
 * bahwa datanya hasil generate. Bila seluruh kandidat sudah terpakai, hidangan
 * ini dilepas begitu saja daripada memakai foto yang menyesatkan.
 */
export function pilihFoto(daftar, dipakai = new Set()) {
    const layak = daftar
        .filter((f) => f.thumb && EKSTENSI_BAIK.test(f.nama) && !FILE_DILARANG.test(f.nama))
        .filter((f) => f.lebar_asli >= 700 && f.tinggi >= 350);

    layak.sort((a, b) => b.lebar_asli - a.lebar_asli);

    return layak.find((f) => !dipakai.has(f.nama)) ?? null;
}

/* ── Saringan kategori ──────────────────────────────────────────────────── */

/**
 * Jangan turun ke sini — isinya bukan hidangan dan tidak mungkin ada resep
 * yang lahir dari keturunannya.
 *
 * Cabang Peranakan dicoret karena isinya didominasi varian lumpia Filipina
 * yang kebetulan salah kategorisasi di Commons, bukan masakan Indonesia.
 */
export const JANGAN_TURUN =
    /(kitchenware|tableware|bowls \(|plates of|plates with|cutlery|sl(aughter|aughtering|aughterhouses)|food trays|by culture|on stamps|kitchens|restaurants|marketplaces?|food stalls|peranakan cuisine)/i;

/**
 * Nama yang jelas bukan satu hidangan, melainkan kategori topik.
 *
 * Pola paling bisa dipercaya: nama hidangan Indonesia tidak pernah memuat
 * kata " of ", " in ", atau " from " di tengah. Yang memuatnya selalu
 * koleksi — "Dishes of Ngawi", "Meals in Indonesia", "Noodles from
 * Indonesia". Karena itu seluruh kecocokan itu ditolak sekaligus.
 */
const TOPIK = /\s+(of|in|from)\s+/i;

/**
 * Kata kerja sifat dan nama bahan bahasa Inggris.
 *
 * Kategori Commons sering dinamai menurut apa yang ada di dalamnya, bukan
 * menurut satu hidangan. "Coconut milk", "Black soybeans", dan "Preserved
 * black beans" adalah bahan. "Salted duck eggs" dan "Sweet and sour fish"
 * adalah terjemahan bebas dari Telur Asin dan Ikan Asam Manis yang sudah
 * punya kategori sendiri dalam bahasa Indonesia.
 */
const JUDUL_INGGRIS =
    /\b(salted|smoked|dried|preserved|sweet and sour|instant|brand)\b|\b(coconut|soybeans?|black beans?|noodles?|shrimp|chicken|beef|duck|pork|rempeyek)\b|^mee\b/i;

/** Kata benda generik yang menandai dokumentasi kegiatan, bukan resep. */
const BUKAN_HIDANGAN =
    /^(wiki|images from|free nutritious|meals? in|school meals|hotel |people |plates|bowls|cutlery|tableware|food trays|chicken (meat|slaughter)|sl(aughter|aughtering|aughterhouses)|buffets?|picnics?|selamatan|rijsttafel|dining|serving|tea serving|desserts?|cookies|crêpes|ice cream|puddings?|rice cakes|rice vermicelli|vermicelli|tiramisu|waffles|yogurt|nata de coco|kolang|chocolate|sauces?|condiments?|spices?|ingredients?|agriculture|fishing|hunting|farming|by |cuisine of|nusantara|makanan |unggahan |chinese |filipino |vietnamese |indian |thai |singaporean |malacca |dutch |arab |indonesian |ragam |kopdar |selected by|jajan pasar|kecap|oncom|tauco|tempeh)/i;

/** Judul yang memuat kata generik di mana pun, mis. "Dishes (tableware)". */
const JUDUL_GENERIK = /( cuisine( of| in)?|\sdishes\s*\(|dutch east indies)/i;

/**
 * Kata yang bila berdiri sendiri tidak menyebut satu hidangan tertentu.
 *
 * Dipakai dua kali: menolak judul satu kata seperti "Bubur" atau "Sate",
 * dan menolak pemakaian artikel Wikipedia generik ("Bubur" = teknik
 * merebus) sebagai deskripsi hidangan yang lebih spesifik ("Bubur randang").
 */
export const KATA_GENERIK = new Set([
    'kue',
    'bubur',
    'sambal',
    'nasi',
    'sate',
    'soto',
    'gulai',
    'semur',
    'opor',
    'dadar',
    'es',
    'lumpia',
    'pecel',
    'ayam',
    'ikan',
    'sayur',
    'mie',
    'tahu',
    'tempe',
    'kari',
    'sup',
    'roti',
    'kerupuk',
    'minuman',
    // Koleksi, bukan satu hidangan. "Dishes (tableware)" dan "Bowls
    // (tableware)" menjadi "Dishes"/"Bowl" setelah tanda kurung dibuang —
    // tanpa kata ini keduanya lolos sebagai judul resep.
    'dishes',
    'bowls',
    'plates',
    'drinks',
    'beverages',
    'meals',
    'foods',
    'desserts',
    'snacks',
    'cakes',
    'breads',
    'crackers',
]);

/**
 * Nama yang tidak bisa ditangkap pola mana pun — kebanyakan hidangan luar
 * Indonesia yang kebetulan terkumpul di pohon kategori kuliner Nusantara.
 * Daftar ini sengaja dibuat pendek: begitu sebuah nama masuk sini, itu
 * berarti aturan umumnya perlu diperbaiki, bukan sekadar ditambahi entri.
 */
const JUDUL_DILARANG = new Set(['yanmian']);

/**
 * Rapikan nama kategori menjadi judul resep.
 *
 * Commons menulis "Ketoprak (food)" dan "Yamin (dish)" untuk membedakannya
 * dari topik lain yang namanya sama. Tanda kurung itu adalah urusan
 * Wikipedia, bukan urusan pembaca resep — "Ketoprak (food)" sebagai judul
 * kartu resep terlihat seperti kesalahan penyusunan.
 */
export function judulBersih(kategori) {
    return kategori
        .replace(/^Category:/, '')
        // Tanda kurung selalu berisi keterangan tambahan dari Wikipedia, bukan
        // bagian nama hidangan: "Ketoprak (food)", "Karak (rice crackers)".
        // Sebagai judul kartu resep keduanya terlihat seperti kesalahan
        // penyusunan.
        .replace(/\s*\([^)]*\)\s*$/, '')
        .trim();
}

/**
 * Apakah nama sebuah kategori layak jadi judul resep?
 *
 * Dibedakan dari filter turun-kategori: sebuah kategori bisa jelas-jelas
 * bukan hidangan sekaligus menjadi wadah bagi hidangan asli di bawahnya.
 * "Vermicelli dishes of Indonesia" bukan resep, tapi "Bihun Goreng" ada.
 *
 * Prefiks "Category:" dibuang sekali di awal. Tanpa ini semua pola yang
 * dibuat lengket pada awal string (^wiki, ^meals) tidak akan pernah cocok,
 * karena yang datang berbentuk "Category:WikiBumbon".
 */
export function layakJadiKandidat(kategori) {
    const isi = judulBersih(kategori);

    if (JUDUL_DILARANG.has(isi.toLowerCase())) {
        return false;
    }

    if (
        TOPIK.test(isi) ||
        BUKAN_HIDANGAN.test(isi) ||
        JUDUL_GENERIK.test(isi) ||
        JUDUL_INGGRIS.test(isi)
    ) {
        return false;
    }

    const kata = isi.split(/\s+/);

    if (kata.length === 1 && KATA_GENERIK.has(isi.toLowerCase())) {
        return false;
    }

    // Terlalu panjang biasanya nama acara atau koleksi
    // ("WikiNusantara 2019 sweets and souvenirs table"), bukan nama hidangan.
    return kata.length <= 6 && isi.length >= 4 && isi.length <= 48;
}

/* ── Klasifikasi kategori resep ─────────────────────────────────────────── */

/**
 * Peta daerah Indonesia, diurutkan dari yang paling spesifik ke yang paling
 * umum. Pemakaian: nama kategori artikel Wikipedia ("Hidangan Jawa Tengah"),
 * jalur kategori Commons ("Cuisine of Bali"), lalu nama hidangan ("Soto
 * Padang"). Urutan ini menentukan hasil, karena "Hidangan Jawa" harus kalah
 * dari "Hidangan Yogyakarta" apa pun urutan kategori di artikel.
 *
 * @type {Array<[RegExp, string]>}
 */
export const ATURAN_DAERAH = [
    [/padang|minang|payakumbuh|solok|sijunjung|bukittinggi/i, 'Sumatera Barat'],
    [/aceh|banda|lhokseumawe/i, 'Aceh'],
    [/palembang|banyuasin|muara enim/i, 'Sumatera Selatan'],
    [/batak|karo|mandailing|simalungun|padang lawas|toba|samosir/i, 'Sumatera Utara'],
    [/lampung|tulang bawang|krui/i, 'Lampung'],
    [/riau|batam|kampar|indragiri|dumai/i, 'Riau'],
    [/jambi|kerinci/i, 'Jambi'],
    [/bengkulu/i, 'Bengkulu'],
    [/bangka|belitung/i, 'Kepulauan Bangka Belitung'],
    [/banjar|banjarmasin|kandangan|martapura/i, 'Kalimantan Selatan'],
    [/pontianak|sambas|kubu|kapuas bar/i, 'Kalimantan Barat'],
    [/balikpapan|samarinda|kutai|tarakan/i, 'Kalimantan Timur'],
    [/palangkaraya|katingan|dayak/i, 'Kalimantan Tengah'],
    [/makassar|bugis|gowa|selayar|toraja|enrekang/i, 'Sulawesi Selatan'],
    [/manado|bitung|minahasa|tomohon/i, 'Sulawesi Utara'],
    [/palu|donggala|poso|sigi/i, 'Sulawesi Tengah'],
    [/kendari|buton|konawe/i, 'Sulawesi Tenggara'],
    [/gorontalo/i, 'Gorontalo'],
    [/bali|balinese|klungkung|karangasem|badung|gilimanuk/i, 'Bali'],
    [/lombok|sumbawa|mataram|sasak/i, 'Nusa Tenggara Barat'],
    [/kupang|flores|timor|ende|sumba|larantuka/i, 'Nusa Tenggara Timur'],
    [/ambon|maluku|ternate|tual|seram/i, 'Maluku'],
    [/papua|jayapura|biak|sorong|manokwari|mimika/i, 'Papua'],
    [/betawi|jakarta/i, 'DKI Jakarta'],
    [/tangerang|serang|cilegon|banten/i, 'Banten'],
    [
        /sunda|sundanese|priangan|pajajaran|cirebon|garut|tasik|bandung|bogor|depok|bekasi|cianjur|sumedang|purwakarta|subang|indramayu|karawang|lebak|pandeglang/i,
        'Jawa Barat',
    ],
    [/yogyakarta|jogja|kraton|bantul|sleman|kulon progo|gunung kidul/i, 'DI Yogyakarta'],
    [
        /jepara|kudus|pekalongan|pati|rembang|tegal|brebes|pemalang|banyumas|purwokerto|wonosobo|magelang|temanggung|salatiga|surakarta|solo|karanganyar|wonogiri|sragen|klaten|boyolali|demak|grobogan|blora|ngawi/i,
        'Jawa Tengah',
    ],
    [
        /surabaya|malang|kediri|blitar|madiun|pasuruan|probolinggo|madura|bangkalan|pamekasan|sumenep|sidoarjo|gresik|lamongan|mojokerto|jombang|nganjuk|tulungagung|trenggalek|ponorogo|pacitan|bojonegoro|magetan|lumajang|situbondo|bondowoso|banyuwangi|jember/i,
        'Jawa Timur',
    ],
    // Cadangan tingkat pulau, sengaja diletakkan paling akhir. Artikel
    // "Gudeg" mengambil "Hidangan Yogyakarta" (indeks 23) alih-alih sekadar
    // "Hidangan Jawa" (indeks ini) hanya karena indeksnya lebih kecil — bukan
    // karena urutan kategori di artikel.
    [/\bjawa\b/i, 'Jawa'],
    [/\bsumat(e|ra)\b/i, 'Sumatera'],
    [/\bsulawesi\b/i, 'Sulawesi'],
    [/\bkalimantan\b/i, 'Kalimantan'],
    [/\bnusa tenggara\b/i, 'Nusa Tenggara'],
];

/**
 * Aturan klasifikasi ke enum `kategori_resep`. Urutan menentukan hasil, jadi
 * yang paling spesifik didahulukan: "Rujak bebek" adalah sambal Madura, bukan
 * hidangan bebek, sehingga aturan Rujak mendahului aturan unggas.
 */
const ATURAN = [
    [/^rujak\b/i, 'sayur_nabati'],
    [/\b(ayam|chicken|bebek|duck|itik|unggas|puyuh|entok|kalkun)\b/i, 'ayam_unggas'],
    [
        /\b(ikan|fish|udang|shrimp|cumi|squid|kerang|kepiting|crab|lobster|tongkol|kembung|gurame|gurami|bandeng|patin|tuna|kakap|teri|pindang|pepes|sate lilit|sapit|rebon|ebi)\b/i,
        'ikan_seafood',
    ],
    [
        /\b(sapi|beef|rendang|semur|dendeng|empal|bakso|tongseng|rawon|kambing|goat|daging|sate padang|sup iga|babi|pork|kurma)\b/i,
        'daging_sapi',
    ],
    [
        /\b(kue|klepon|dodol|getuk|cireng|martabak|serabi|lemper|pastel|risol|lapis|onde|bika|roti|biskuit|donat|lumpia|loempia|pisang goreng|wajik|jenang|nagasari|lupis|cenil|kembang goyang|bolen|misro|combro|batagor|siomay|pempek|gejrot|sagu|apem|bolu|pukis|talam|putu|loder|engkak|kelempang|inasua|model gandum|bakcang|ngohiong|sumpia|turon|es |ice |juice|jus |teh |kopi|susu|nata|kolang|tiramisu|waffle|crêpe|crepe|pudding|cookie|bubuh)\b/i,
        'jajanan',
    ],
    [
        /\b(sayur|lodeh|urap|plecing|gudeg|pecel|botok|tahu|tempe|capcay|sayuran|vegetable|kangkung|bayam|asinan|kelor|lalapan|daun)\b/i,
        'sayur_nabati',
    ],
    [
        /\b(nasi|goreng|lontong|ketupat|mie|bakmi|pangsit|bubur|soto|sate|satay|kikil|gulai|kwetiau|tumpeng|megono|uduk|campur|liwet|magelangan|bihun|cakwe)\b/i,
        'nasi_karbo',
    ],
];

export function klasifikasi(judul) {
    for (const [polos, hasil] of ATURAN) {
        if (polos.test(judul)) {
            return hasil;
        }
    }

    // Cadangan terakhir: nama hidangan Indonesia yang tidak terpetakan akan
    // masuk jajanan, kategori paling longgar, alih-alih menolak datanya.
    return 'jajanan';
}

/** Ambil nama daerah dari jalur kategori lebih dulu, baru dari judul. */
export function daerahDari(jalur, judul, artikel = []) {
    // Kategori artikel Wikipedia ("Hidangan Jawa Tengah", "Hidangan
    // Palembang") adalah sumber paling bisa dipercaya: disusun oleh penyunting
    // manusia yang tahu hidangan itu berasal dari mana. Jalur kategori Commons
    // sering hanya "Cuisine of Indonesia" dan karena itu tidak menyebut daerah.
    const sumber = [...artikel.map((k) => `Kategori:${k}`), ...jalur];

    let peringkatTerbaik = Infinity;
    let daerahTerbaik = null;

    for (const kategori of sumber) {
        const nama = kategori.replace(/^Category:/, '');

        ATURAN_DAERAH.forEach(([polos, hasil], peringkat) => {
            if (peringkat < peringkatTerbaik && polos.test(nama)) {
                peringkatTerbaik = peringkat;
                daerahTerbaik = hasil;
            }
        });
    }

    if (daerahTerbaik) {
        return daerahTerbaik;
    }

    for (const [polos, hasil] of ATURAN_DAERAH) {
        if (polos.test(judul)) {
            return hasil;
        }
    }

    return null;
}

export function slugify(teks) {
    return teks
        .toLowerCase()
        .normalize('NFKD')
        .replace(/[̀-ͯ]/g, '')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '')
        .slice(0, 60);
}

/** Buang tag HTML dan spasi berlebih dari metadata Commons. */
export function bersih(html) {
    if (!html) {
        return null;
    }

    const polos = String(html)
        .replace(/<[^>]*>/g, '')
        .replace(/\s+/g, ' ')
        .trim();

    return polos.length > 0 && polos.length <= 120 ? polos : null;
}
