/**
 * Pratinjau kalkulasi gizi di sisi klien.
 *
 * INI BUKAN SUMBER KEBENARAN. Nilai gizi yang tersimpan selalu dihitung
 * ulang oleh server (`App\Services\KalkulasiGizi`) saat resep disimpan —
 * dan angka itulah yang tampil ke publik. Fungsi di sini hanya
 * MEMPREDIKSI hasil hitungan saat kreator mengetik, supaya panel gizi
 * terasa hidup tanpa harus menunggu satu permintaan server tiap baris
 * bahan berubah.
 *
 * Konsekuensinya: aturan konversi di file ini harus selaras dengan
 * KalkulasiGizi.php. Bila salah satunya diubah, yang lain harus ikut —
 * silakan lihat catatan di KalkulasiGizi untuk rincian tiap satuan.
 */

// Volume sendok takar dapur, mililiter.
const SENDOK_TEH_ML = 5;
const SENDOK_MAKAN_ML = 15;

// Satuan yang takarannya memang berat — dipakai apa adanya.
const SATUAN_BERAT = ['gram', 'gr', 'g', 'kg', 'kilogram'];

// Satuan "hitungan benda" — butuh berat_per_unit dari rujukan bahan.
const SATUAN_BUTIR = [
    'buah',
    'siung',
    'butir',
    'ruas',
    'lembar',
    'ikat',
    'genggam',
    'batang',
    'ekor',
    'iris',
    'slice',
    'sachet',
    'bungkus',
];

const namaNormal = (nama) => String(nama ?? '').trim().replace(/\s+/g, ' ').toLowerCase();

/**
 * Konversi satu baris bahan menjadi gram, memakai rujukan bahan.
 * Mengembalikan null bila tak bisa dikonversi.
 */
function keGram(baris, rujukan) {
    const jumlah = Number(baris.jumlah) || 0;
    const satuan = String(baris.satuan ?? 'gram').trim().toLowerCase();

    if (jumlah <= 0) return null;

    const densitas = Number(rujukan?.faktor_densitas) || 1;

    if (SATUAN_BERAT.includes(satuan)) {
        return satuan === 'kg' || satuan === 'kilogram' ? jumlah * 1000 : jumlah;
    }

    switch (satuan) {
        case 'ml':
        case 'mililiter':
        case 'cc':
            return jumlah * densitas;
        case 'liter':
        case 'l':
            return jumlah * 1000 * densitas;
        case 'sdt':
        case 'sendok teh':
            return SENDOK_TEH_ML * jumlah * densitas;
        case 'sdm':
        case 'sendok makan':
            return SENDOK_MAKAN_ML * jumlah * densitas;
        default:
            return keGramButir(jumlah, satuan, rujukan);
    }
}

function keGramButir(jumlah, satuan, rujukan) {
    if (!SATUAN_BUTIR.includes(satuan)) return null;

    const berat = rujukan?.berat_per_unit;

    // Bahan tanpa berat_per_unit tidak bisa mengubah "1 siung" jadi gram.
    if (berat === null || berat === undefined || berat === '') return null;

    return jumlah * Number(berat);
}

/**
 * Cari rujukan bahan untuk satu baris — lewat bahan_id, lalu lewat nama.
 */
function resolusi(baris, petaId, petaNama) {
    if (baris.bahan_id != null && petaId.has(Number(baris.bahan_id))) {
        return petaId.get(Number(baris.bahan_id));
    }

    const nama = namaNormal(baris.nama);

    if (!nama) return null;

    return petaNama.get(nama) ?? null;
}

/**
 * Hitung gizi per porsi dari daftar bahan resep.
 *
 * @param {Array}  bahanResep  Isi kolom jsonb `bahan` resep.
 * @param {number} porsi       Jumlah porsi resep.
 * @param {Array}  bahanTersedia Rujukan bahan_pangan dari server.
 * @returns {{
 *   energi_kkal: number, protein_g: number, lemak_g: number, karbohidrat_g: number,
 *   cakupan: number, totalBahan: number, terhitung: number,
 *   takTerhitung: Array<{nama: string, alasan: string}>
 * }}
 */
export function hitungGizi(bahanResep = [], porsi = 1, bahanTersedia = []) {
    const efektif = Math.max(1, Number(porsi) || 1);

    const petaId = new Map();
    const petaNama = new Map();

    for (const b of bahanTersedia) {
        petaId.set(Number(b.id), b);
        petaNama.set(namaNormal(b.nama), b);
    }

    const total = { energi_kkal: 0, protein_g: 0, lemak_g: 0, karbohidrat_g: 0 };
    const takTerhitung = [];
    let terhitung = 0;

    for (const baris of bahanResep) {
        const nama = String(baris.nama ?? '(tanpa nama)');
        const rujukan = resolusi(baris, petaId, petaNama);

        if (!rujukan) {
            takTerhitung.push({
                nama,
                alasan: namaNormal(baris.nama)
                    ? 'tidak ada di rujukan bahan'
                    : 'nama bahan kosong',
            });
            continue;
        }

        const gram = keGram(baris, rujukan);

        if (gram === null) {
            const satuan = String(baris.satuan ?? '').trim().toLowerCase();
            takTerhitung.push({
                nama,
                alasan:
                    !satuan || satuan === 'secukupnya'
                        ? 'takaran "secukupnya" tidak bisa dihitung otomatis'
                        : `satuan "${satuan}" tidak dikenal`,
            });
            continue;
        }

        // Nilai gizi per gram — menyesuaikan apakah rujukan berbasis
        // berat (per 100 g) atau cair (per 100 ml, ikut densitas).
        const pembagi =
            String(rujukan.satuan_dasar) === 'ml'
                ? 100 * (Number(rujukan.faktor_densitas) || 1)
                : 100;

        total.energi_kkal += (gram * (Number(rujukan.energi_kkal) || 0)) / pembagi;
        total.protein_g += (gram * (Number(rujukan.protein_g) || 0)) / pembagi;
        total.lemak_g += (gram * (Number(rujukan.lemak_g) || 0)) / pembagi;
        total.karbohidrat_g += (gram * (Number(rujukan.karbohidrat_g) || 0)) / pembagi;

        terhitung++;
    }

    const totalBahan = bahanResep.length;
    const cakupan = totalBahan === 0 ? 0 : Math.round((terhitung / totalBahan) * 100);

    return {
        energi_kkal: round(total.energi_kkal / efektif),
        protein_g: round(total.protein_g / efektif),
        lemak_g: round(total.lemak_g / efektif),
        karbohidrat_g: round(total.karbohidrat_g / efektif),
        cakupan,
        totalBahan,
        terhitung,
        takTerhitung,
    };
}

/**
 * Satuan yang disarankan untuk satu rujukan bahan.
 *
 * Cairan disarankan ml, sisanya gram — supaya kreator tidak memilih
 * satuan yang justru membuat takarannya sulit dikonversi.
 */
export function satuanDisarankan(rujukan) {
    if (!rujukan) return 'gram';

    return String(rujukan.satuan_dasar) === 'ml' ? 'ml' : 'gram';
}

function round(nilai) {
    return Math.round(nilai * 100) / 100;
}
