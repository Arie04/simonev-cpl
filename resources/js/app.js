import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

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

/**
 * Membaca token warna dari CSS (bagian 7.2), mis. warna('success-chart') → '#16a34a'.
 * Dengan begitu grafik memakai palet yang sama dengan halaman, tanpa menulis hex di sini.
 */
function warna(token) {
    return getComputedStyle(document.documentElement).getPropertyValue(`--color-${token}`).trim();
}

function formatAngka(nilai) {
    return nilai.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

/*
 * Grafik batang laporan (bagian 7.6.9): nilai per item dengan label angka di atas batang dan
 * garis putus-putus Ambang Batas Ketercapaian. Batang hijau jika Tercapai, merah jika tidak,
 * dan biru jika ambang belum ditetapkan (bagian 7.2: biru bukan warna status).
 */
Alpine.data('grafikLaporan', (data) => ({
    init() {
        const warnaStatus = { tercapai: warna('success-chart'), tidak: warna('danger-chart'), netral: warna('primary-700') };
        // Di layar kecil dengan banyak item, batang dibuat mendatar supaya labelnya terbaca (bagian 7.4).
        // Label "Sub-CPMK0811 · MK04" dipecah dua baris, dan tinggi grafik mengikuti jumlah item.
        const mendatar = window.matchMedia('(max-width: 767px)').matches && data.label.length > 6;
        const sumbuNilai = mendatar ? 'x' : 'y';
        const sumbuItem = mendatar ? 'y' : 'x';
        const label = mendatar ? data.label.map((teks) => teks.split(' · ')) : data.label;

        if (mendatar) {
            this.$el.style.height = `${Math.max(240, data.label.length * 44)}px`;
        }

        const labelAngka = {
            id: 'labelAngka',
            afterDatasetsDraw(chart) {
                const { ctx } = chart;
                ctx.save();
                ctx.font = '600 11px "Plus Jakarta Sans", system-ui, sans-serif';
                ctx.fillStyle = warna('text');
                ctx.textAlign = mendatar ? 'left' : 'center';
                ctx.textBaseline = mendatar ? 'middle' : 'bottom';
                chart.getDatasetMeta(0).data.forEach((batang, indeks) => {
                    const nilai = data.nilai[indeks];
                    if (nilai !== null) {
                        ctx.fillText(formatAngka(nilai), mendatar ? batang.x + 4 : batang.x, mendatar ? batang.y : batang.y - 4);
                    }
                });
                ctx.restore();
            },
        };

        const daftarDataset = [{
            type: 'bar',
            label: data.labelNilai,
            data: data.nilai,
            backgroundColor: data.status.map((status) => warnaStatus[status]),
            borderRadius: 4,
            maxBarThickness: 40,
        }];

        if (data.ambangKetercapaian !== null) {
            daftarDataset.push({
                type: 'line',
                label: `Ambang Batas Ketercapaian (${formatAngka(data.ambangKetercapaian)}%)`,
                data: data.label.map(() => data.ambangKetercapaian),
                borderColor: warna('text-muted'),
                borderDash: [6, 4],
                borderWidth: 1.5,
                pointRadius: 0,
            });
        }

        new Chart(this.$refs.kanvas, {
            data: { labels: label, datasets: daftarDataset },
            options: {
                indexAxis: mendatar ? 'y' : 'x',
                responsive: true,
                maintainAspectRatio: false,
                layout: { padding: { top: 18, right: mendatar ? 48 : 0 } },
                scales: {
                    [sumbuNilai]: { min: 0, max: 100, ticks: { callback: (nilai) => `${nilai}%` } },
                    [sumbuItem]: { ticks: { autoSkip: false, font: { size: 11 } } },
                },
                plugins: {
                    legend: { display: data.ambangKetercapaian !== null, position: 'bottom', labels: { filter: (item) => item.datasetIndex === 1 } },
                    tooltip: { callbacks: { label: (konteks) => `${konteks.dataset.label}: ${formatAngka(konteks.parsed[sumbuNilai])}%` } },
                },
            },
            plugins: [labelAngka],
        });
    },
}));

/*
 * Donut Ringkasan Capaian CPL di Dashboard Koprodi (bagian 7.6.2): hijau Tercapai, merah Tidak
 * Tercapai, abu Belum ada nilai (KD-40).
 */
Alpine.data('grafikDonut', (data) => ({
    init() {
        new Chart(this.$refs.kanvas, {
            type: 'doughnut',
            data: {
                labels: data.label,
                datasets: [{
                    data: data.nilai,
                    backgroundColor: [warna('success-chart'), warna('danger-chart'), `${warna('neutral')}66`],
                    borderColor: warna('surface'),
                    borderWidth: 2,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
                // Label kategori cukup panjang, jadi legenda selalu di bawah grafik (bagian 7.4).
                plugins: { legend: { position: 'bottom' } },
            },
        });
    },
}));

window.Alpine = Alpine;
Alpine.start();
