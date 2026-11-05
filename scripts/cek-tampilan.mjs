/*
 * Pengecekan tampilan (skenario uji 16 dan bagian 7.4 spesifikasi).
 *
 * Skrip ini membuka setiap halaman untuk setiap peran di lebar 360, 768, dan 1280 px dengan
 * Google Chrome tanpa jendela (headless), lalu:
 * - memeriksa apakah halaman bisa digeser horizontal (scrollWidth > lebar layar);
 * - di lebar mobile, memeriksa ukuran font input (minimal 16px) dan target sentuh tombol (minimal
 *   44×44 px);
 * - mencatat error JavaScript;
 * - menyimpan tangkapan layar ke storage/app/tangkapan-layar/<lebar>/ untuk Bab 4.
 *
 * Syarat: data demo (`php artisan migrate:fresh --seed`), aset sudah di-build (`npm run build`),
 * dan aplikasi berjalan (`php artisan serve`). Jalankan dengan `npm run cek:tampilan`.
 * Alamat dan lokasi Chrome bisa diganti lewat variabel APP_URL dan CHROME_PATH.
 */
import puppeteer from 'puppeteer-core';
import { mkdirSync } from 'node:fs';

const ALAMAT = process.env.APP_URL ?? 'http://127.0.0.1:8000';
const CHROME = process.env.CHROME_PATH ?? '/usr/bin/google-chrome';
const FOLDER = new URL('../storage/app/tangkapan-layar/', import.meta.url).pathname;
const DAFTAR_LEBAR = [360, 768, 1280];

/*
 * Halaman per akun demo. Entri berupa alamat langsung, atau { dari, ikuti } untuk halaman detail:
 * buka alamat `dari`, lalu ikuti tautan pertama yang cocok dengan selector `ikuti`.
 */
const SKENARIO = [
    {
        akun: null,
        halaman: [{ nama: 'login', alamat: '/login' }],
    },
    {
        akun: { identitas: 'admin@simonev.test', password: 'admin123' },
        halaman: [
            { nama: 'admin-beranda', alamat: '/admin' },
            { nama: 'admin-program-studi', alamat: '/admin/program-studi' },
            { nama: 'admin-kurikulum', alamat: '/admin/kurikulum' },
            { nama: 'admin-evaluasi-kurikulum', alamat: '/admin/evaluasi-kurikulum' },
            { nama: 'admin-profil-lulusan', alamat: '/admin/profil-lulusan' },
            { nama: 'admin-profil-lulusan-form', alamat: '/admin/profil-lulusan/create' },
            { nama: 'admin-profesi', alamat: '/admin/profesi' },
            { nama: 'admin-cpl', alamat: '/admin/cpl' },
            { nama: 'admin-cpl-form', alamat: '/admin/cpl/create' },
            { nama: 'admin-bahan-kajian', alamat: '/admin/bahan-kajian' },
            { nama: 'admin-mata-kuliah', alamat: '/admin/mata-kuliah' },
            { nama: 'admin-mata-kuliah-detail', dari: '/admin/mata-kuliah', ikuti: 'a[aria-label^="Lihat detail"]' },
            { nama: 'admin-cpmk', alamat: '/admin/cpmk' },
            { nama: 'admin-sub-cpmk', alamat: '/admin/sub-cpmk' },
            { nama: 'admin-sub-cpmk-detail', dari: '/admin/sub-cpmk', ikuti: 'a[aria-label^="Lihat detail"]' },
            { nama: 'admin-metode-penilaian', alamat: '/admin/metode-penilaian' },
            { nama: 'admin-dosen', alamat: '/admin/dosen' },
            { nama: 'admin-mahasiswa', alamat: '/admin/mahasiswa' },
            { nama: 'admin-pemetaan-cpl-pl', alamat: '/admin/pemetaan?jenis=cpl-pl' },
            { nama: 'admin-pemetaan-cpl-bk', alamat: '/admin/pemetaan?jenis=cpl-bk' },
            { nama: 'admin-pemetaan-bk-mk', alamat: '/admin/pemetaan?jenis=bk-mk' },
            { nama: 'admin-pemetaan-cpmk-mk', alamat: '/admin/pemetaan?jenis=cpmk-mk' },
            { nama: 'admin-periode-akademik', alamat: '/admin/periode-akademik' },
            { nama: 'admin-kelas-kuliah', alamat: '/admin/kelas-kuliah' },
            { nama: 'admin-kelas-kuliah-detail', dari: '/admin/kelas-kuliah', ikuti: 'a[aria-label^="Lihat detail"]' },
            { nama: 'admin-kelas-kuliah-form', alamat: '/admin/kelas-kuliah/create' },
            { nama: 'admin-akun', alamat: '/admin/akun' },
            { nama: 'admin-akun-form', alamat: '/admin/akun/create' },
            { nama: 'profil', alamat: '/profil' },
            { nama: 'ubah-password', alamat: '/ubah-password' },
            { nama: 'error-404', alamat: '/halaman-tidak-ada' },
        ],
    },
    {
        akun: { identitas: 'koprodi@simonev.test', password: 'koprodi123' },
        halaman: [
            { nama: 'koprodi-dashboard', alamat: '/koprodi' },
            { nama: 'koprodi-data-kurikulum', alamat: '/koprodi/kurikulum?tab=ringkasan' },
            { nama: 'koprodi-data-kurikulum-mata-kuliah', alamat: '/koprodi/kurikulum?tab=mata-kuliah' },
            { nama: 'koprodi-data-kurikulum-pemetaan', alamat: '/koprodi/kurikulum?tab=pemetaan&jenis=cpmk-mk' },
            { nama: 'koprodi-laporan-cpl', alamat: '/koprodi/laporan?tab=cpl' },
            { nama: 'koprodi-laporan-cpmk', alamat: '/koprodi/laporan?tab=cpmk' },
            { nama: 'koprodi-laporan-sub-cpmk', alamat: '/koprodi/laporan?tab=sub-cpmk' },
            { nama: 'error-403', alamat: '/admin' },
        ],
    },
    {
        akun: { identitas: 'koprodi.dosen@simonev.test', password: 'koprodi.dosen123', peran: 'Dosen' },
        halaman: [{ nama: 'pilih-peran', alamat: '/pilih-peran' }],
    },
    {
        akun: { identitas: '9990000001', password: 'dosen123' },
        halaman: [
            { nama: 'dosen-dashboard', alamat: '/dosen' },
            { nama: 'dosen-mata-kuliah', alamat: '/dosen/mata-kuliah' },
            { nama: 'dosen-mata-kuliah-detail', dari: '/dosen/mata-kuliah', ikuti: 'a[aria-label^="Lihat CPL"]' },
            { nama: 'dosen-rencana-asesmen-kelas', alamat: '/dosen/rencana-asesmen' },
            { nama: 'dosen-rencana-asesmen', dari: '/dosen/rencana-asesmen', ikuti: 'a[href*="/dosen/kelas/"]' },
            { nama: 'dosen-rencana-asesmen-form', dari: '/dosen/rencana-asesmen', ikuti: 'a[href*="/dosen/kelas/"]', lalu: 'a[href$="/rencana-asesmen/create"]' },
            { nama: 'dosen-input-nilai', alamat: '/dosen/nilai' },
            { nama: 'dosen-input-nilai-tabel', dari: '/dosen/rencana-asesmen', ikuti: 'a[href*="/dosen/kelas/"]', lalu: 'a[aria-label^="Input nilai"]' },
            { nama: 'dosen-laporan', alamat: '/dosen/laporan?tab=cpmk' },
        ],
    },
    {
        akun: { identitas: '9992300001', password: 'mahasiswa123' },
        halaman: [
            { nama: 'mahasiswa-dashboard', alamat: '/mahasiswa' },
            { nama: 'mahasiswa-capaian', alamat: '/mahasiswa/capaian' },
        ],
    },
    {
        // Hendra Kurniawan punya kelas di dua periode, jadi grafik perkembangan CPL-nya tampil.
        akun: { identitas: '9992400001', password: 'mahasiswa123' },
        halaman: [{ nama: 'mahasiswa-dashboard-perkembangan', alamat: '/mahasiswa' }],
    },
];

const jeda = (milidetik) => new Promise((selesai) => setTimeout(selesai, milidetik));

/*
 * Pemeriksaan khusus mobile (bagian 7.4): font input minimal 16px agar Safari iOS tidak
 * memperbesar halaman, dan target sentuh tombol, tautan ikon, serta lingkaran matriks minimal 44×44 px.
 */
function periksaMobile() {
    const terlihat = (elemen) => {
        const kotak = elemen.getBoundingClientRect();

        return kotak.width > 0 && kotak.height > 0 && getComputedStyle(elemen).visibility !== 'hidden';
    };
    const nama = (elemen) => elemen.getAttribute('aria-label') ?? elemen.getAttribute('name') ?? elemen.textContent.trim().replace(/\s+/g, ' ').slice(0, 30);
    const hasil = [];

    for (const elemen of document.querySelectorAll('input:not([type=hidden]):not([type=checkbox]):not([type=radio]), select, textarea')) {
        if (terlihat(elemen) && parseFloat(getComputedStyle(elemen).fontSize) < 16) {
            hasil.push(`font input "${nama(elemen)}" di bawah 16px`);
        }
    }

    for (const elemen of document.querySelectorAll('button, a[aria-label], label:has(> input[type=checkbox])')) {
        const kotak = elemen.getBoundingClientRect();

        if (terlihat(elemen) && (kotak.width < 44 || kotak.height < 44)) {
            hasil.push(`target sentuh "${nama(elemen)}" ${Math.round(kotak.width)}×${Math.round(kotak.height)} px`);
        }
    }

    return [...new Set(hasil)];
}

async function login(page, akun) {
    await page.goto(`${ALAMAT}/login`, { waitUntil: 'networkidle0' });
    await page.type('#identitas', akun.identitas);
    await page.type('#password', akun.password);
    await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0' }), page.click('button[type=submit]')]);

    // Akun berperan dua memilih peran dulu, kecuali halaman yang diperiksa adalah Pilih Peran itu sendiri.
    if (akun.peran === undefined && page.url().includes('/pilih-peran')) {
        const tombol = await page.$('form button[type=submit]');
        await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0' }), tombol.click()]);
    }
}

async function buka(page, halaman) {
    if (halaman.alamat) {
        return page.goto(`${ALAMAT}${halaman.alamat}`, { waitUntil: 'networkidle0' });
    }

    await page.goto(`${ALAMAT}${halaman.dari}`, { waitUntil: 'networkidle0' });
    let respons = null;

    for (const selector of [halaman.ikuti, halaman.lalu].filter(Boolean)) {
        const tautan = await page.$(selector);

        if (tautan === null) {
            throw new Error(`tautan "${selector}" tidak ditemukan`);
        }

        [respons] = await Promise.all([page.waitForNavigation({ waitUntil: 'networkidle0' }), tautan.click()]);
    }

    return respons;
}

const browser = await puppeteer.launch({ executablePath: CHROME, headless: true, args: ['--no-first-run'] });
// Masalah yang sama sering muncul di banyak halaman, jadi dikelompokkan: pesan => daftar halaman.
const daftarMasalah = new Map();
const catat = (lebar, namaHalaman, pesan) => {
    const kunci = `${lebar}px: ${pesan}`;
    daftarMasalah.set(kunci, [...(daftarMasalah.get(kunci) ?? []), namaHalaman]);
};
let jumlahHalaman = 0;

for (const lebar of DAFTAR_LEBAR) {
    mkdirSync(`${FOLDER}${lebar}`, { recursive: true });

    for (const { akun, halaman: daftarHalaman } of SKENARIO) {
        const konteks = await browser.createBrowserContext();
        const page = await konteks.newPage();
        await page.setViewport({ width: lebar, height: 800 });
        const daftarError = [];
        page.on('pageerror', (error) => daftarError.push(error.message));

        if (akun) {
            await login(page, akun);
        }

        for (const halaman of daftarHalaman) {
            try {
                const respons = await buka(page, halaman);
                await jeda(400); // memberi waktu Alpine dan Chart.js menggambar
                const ukuran = await page.evaluate(() => ({ lebarIsi: document.documentElement.scrollWidth, lebarLayar: window.innerWidth }));
                await page.screenshot({ path: `${FOLDER}${lebar}/${halaman.nama}.png`, fullPage: true });
                jumlahHalaman++;

                const status = respons?.status() ?? 200;
                const statusDiharapkan = halaman.nama.startsWith('error-') ? Number(halaman.nama.slice(6)) : 200;

                if (status !== statusDiharapkan) {
                    catat(lebar, halaman.nama, `status ${status}, seharusnya ${statusDiharapkan}`);
                }

                if (ukuran.lebarIsi > ukuran.lebarLayar) {
                    catat(lebar, halaman.nama, `halaman bergeser horizontal (${ukuran.lebarIsi} > ${ukuran.lebarLayar} px)`);
                }

                if (lebar < 768) {
                    (await page.evaluate(periksaMobile)).forEach((pesan) => catat(lebar, halaman.nama, pesan));
                }
            } catch (error) {
                catat(lebar, halaman.nama, error.message);
            }
        }

        for (const pesan of new Set(daftarError)) {
            catat(lebar, akun?.identitas ?? 'tamu', `error JavaScript: ${pesan}`);
        }

        await konteks.close();
    }
}

await browser.close();

console.log(`${jumlahHalaman} tangkapan layar disimpan di storage/app/tangkapan-layar/.`);

if (daftarMasalah.size > 0) {
    console.log(`Ditemukan ${daftarMasalah.size} jenis masalah:`);
    daftarMasalah.forEach((daftarHalaman, masalah) => console.log(`- ${masalah} (${daftarHalaman.length} halaman: ${daftarHalaman.join(', ')})`));
    process.exitCode = 1;
} else {
    console.log('Semua halaman lolos: tidak bergeser horizontal, status benar, tanpa error JavaScript,');
    console.log('font input di mobile minimal 16px, dan target sentuh minimal 44×44 px.');
}
