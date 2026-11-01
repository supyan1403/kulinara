import { readFile } from 'node:fs/promises';
import {
    layakJadiKandidat,
    klasifikasi,
    JANGAN_TURUN,
} from './panen-filter.mjs';

const sumber = process.argv[2] || 'database/seeders/data/_cache-panen.json';
const cache = JSON.parse(await readFile(sumber, 'utf8'));

const semua = Object.keys(cache).filter((x) => x !== 'Category:Cuisine of Indonesia');
const lolos = semua.filter(layakJadiKandidat);
const tolak = semua.filter((x) => !layakJadiKandidat(x));
const diblokirTurun = semua.filter((x) => JANGAN_TURUN.test(x));

console.log(`KATEGORI LOLOS JADI KANDIDAT (${lolos.length}):\n`);
lolos.forEach((x) => console.log('   ', x.replace('Category:', '')));

console.log(`\nKATEGORI DITOLAK (${tolak.length}):\n`);
tolak.forEach((x) => console.log('   ', x.replace('Category:', '')));

console.log(`\nDIBLOKIR TURUN (${diblokirTurun.length}):\n`);
diblokirTurun.forEach((x) => console.log('   ', x.replace('Category:', '')));

console.log('\nKLASIFIKASI CONTOH:\n');
for (const j of [
    'Soto Padang',
    'Rujak bebek',
    'Nasi goreng kambing',
    'Bubur ayam',
    'Es teler',
    'Pindang Tembakang',
    'Pepes Mlanding Teri',
    'Lumpia Surabaya',
    'Kue apem',
    'Lontong sayur',
    'Sambal nanas',
    'Bubur gunting Banjar',
]) {
    console.log('   ', j.padEnd(28), klasifikasi(j));
}
