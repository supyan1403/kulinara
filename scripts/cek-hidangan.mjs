import { readFile } from 'node:fs/promises';

/**
 * Periksa hidangan hasil panen: pastikan tidak ada kategori topik, bahan,
 * atau nama asing yang tersisa sebagai judul resep.
 *
 * Pakai:
 *   node scripts/cek-hidangan.mjs
 */

const SISA_JUNK = [
    'Salted duck eggs',
    'Sweet and sour fish',
    'Sambal shrimp',
    'Shrimp rempeyek',
    'Black soybeans',
    'Coconut milk',
    'Preserved black beans',
    'Noodles from Indonesia',
    'Indomie instant noodles',
    'Kecap',
    'Oncom',
    'Tauco',
    'Tempeh',
    'Jajan pasar',
    'Mee Rojak',
    'Yanmian',
    'Ketoprak (food)',
    'Yamin (dish)',
    'Turon (food)',
    'Dishes of Ngawi',
];

const hidangan = JSON.parse(
    await readFile('database/seeders/data/hidangan.json', 'utf8'),
);

const ketahuan = SISA_JUNK.filter((j) => hidangan.some((h) => h.judul === j));

console.log(ketahuan.length === 0
    ? 'SEMUA JUNK TERBUANG ✓'
    : `${ketahuan.length} MASIH ADA:\n  ${ketahuan.join('\n  ')}`);

const berkurung = hidangan.filter((h) => /\(/.test(h.judul));
console.log('\njudul bertanda kurung:', berkurung.map((h) => h.judul).join(', ') || '(tidak ada)');

const duplikatJudul = [...hidangan.reduce((m, h) => m.set(h.judul.toLowerCase(), (m.get(h.judul.toLowerCase()) ?? 0) + 1), new Map())]
    .filter(([, n]) => n > 1);
console.log('judul kembar:', duplikatJudul.map(([j, n]) => `${j}×${n}`).join(', ') || '(tidak ada)');

const duplikatSlug = [...hidangan.reduce((m, h) => m.set(h.slug, (m.get(h.slug) ?? 0) + 1), new Map())]
    .filter(([, n]) => n > 1);
console.log('slug kembar:', duplikatSlug.map(([s, n]) => `${s}×${n}`).join(', ') || '(tidak ada)');

const duplikatFoto = [...hidangan.reduce((m, h) => m.set(h.foto.nama_berkas, (m.get(h.foto.nama_berkas) ?? 0) + 1), new Map())]
    .filter(([, n]) => n > 1);
console.log('foto kembar:', duplikatFoto.map(([f, n]) => `${f}×${n}`).join(', ') || '(tidak ada)');

console.log('\nper kategori:', JSON.stringify(
    hidangan.reduce((a, h) => (a[h.kategori] = (a[h.kategori] ?? 0) + 1, a), {}),
));

console.log('dengan daerah:', hidangan.filter((h) => h.daerah).length, 'dari', hidangan.length);
console.log('dengan deskripsi asli:', hidangan.filter((h) => h.deskripsi).length, 'dari', hidangan.length);
console.log('total:', hidangan.length);
