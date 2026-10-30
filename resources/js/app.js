import Alpine from 'alpinejs';

/*
 * Status sidebar untuk layout utama (bagian 7.4 spesifikasi).
 * Di layar ≥ 1024px tombol hamburger menyembunyikan/menampilkan sidebar;
 * di layar lebih kecil sidebar muncul sebagai drawer dari kiri.
 */
Alpine.data('tataLetak', () => ({
    sidebarDesktop: true,
    drawerMobile: false,

    alihkanSidebar() {
        if (window.matchMedia('(min-width: 1024px)').matches) {
            this.sidebarDesktop = !this.sidebarDesktop;
        } else {
            this.drawerMobile = !this.drawerMobile;
        }
    },

    tutupDrawer() {
        this.drawerMobile = false;
    },
}));

/*
 * Form rencana asesmen (bagian 7.6.7): menghitung Σ bobot kontribusi secara langsung, supaya
 * dosen tahu apakah jumlahnya sudah sama dengan bobot asesmen sebelum menyimpan. Validasi
 * sebenarnya tetap dilakukan di server (RencanaAsesmenRequest).
 */
Alpine.data('formAsesmen', (awal) => ({
    bobotAsesmen: awal.bobotAsesmen,
    subCpmk: awal.subCpmk, // { idSubCpmk: { dipilih: true/false, kontribusi: '10' } }

    angka(teks) {
        const nilai = parseFloat(String(teks ?? '').replace(',', '.'));

        return Number.isNaN(nilai) ? 0 : nilai;
    },

    get totalKontribusi() {
        return Object.values(this.subCpmk)
            .filter((baris) => baris.dipilih)
            .reduce((jumlah, baris) => jumlah + this.angka(baris.kontribusi), 0);
    },

    get sesuai() {
        return Math.abs(this.totalKontribusi - this.angka(this.bobotAsesmen)) < 0.005;
    },

    format(nilai) {
        return nilai.toLocaleString('id-ID', { maximumFractionDigits: 2 });
    },
}));

/*
 * Form input nilai (bagian 7.4): memberi peringatan jika pengguna meninggalkan halaman
 * sebelum menyimpan nilai yang sudah diubah.
 */
Alpine.data('formNilai', () => ({
    berubah: false,

    init() {
        window.addEventListener('beforeunload', (event) => {
            if (this.berubah) {
                event.preventDefault();
                event.returnValue = '';
            }
        });
    },
}));

window.Alpine = Alpine;
Alpine.start();
