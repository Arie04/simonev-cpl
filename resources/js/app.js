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

/**
 * Plugin Chart.js yang menulis angka di ujung setiap batang, karena status tidak boleh hanya
 * ditunjukkan lewat warna (bagian 7.2). Item tanpa nilai diberi teksKosong (mis. "–") di dekat
 * garis dasar, atau tidak diberi label jika teksKosong null. Dipakai grafik laporan dan grafik
 * CPL di Dashboard Mahasiswa.
 */
function pluginLabelAngka(daftarNilai, mendatar, teksKosong = null) {
    return {
        id: 'labelAngka',
        afterDatasetsDraw(chart) {
            const { ctx } = chart;
            const daftarBatang = chart.getDatasetMeta(0).data;
            ctx.save();
            ctx.font = '600 11px "Plus Jakarta Sans", system-ui, sans-serif';
            ctx.fillStyle = warna('text');
            ctx.textAlign = mendatar ? 'left' : 'center';
            ctx.textBaseline = mendatar ? 'middle' : 'bottom';
            daftarNilai.forEach((nilai, indeks) => {
                const batang = daftarBatang[indeks];
                if (!batang || (nilai === null && teksKosong === null)) {
                    return;
                }
                const teks = nilai === null ? teksKosong : formatAngka(nilai);
                // Posisi diambil dari batang supaya label ikut animasi batang. Item tanpa nilai
                // tidak punya batang, jadi labelnya diletakkan di garis dasar (nilai 0).
                const posisiItem = mendatar ? batang.y : batang.x;
                const posisiDasar = chart.scales[mendatar ? 'x' : 'y'].getPixelForValue(0);
                const posisiNilai = nilai === null ? posisiDasar : (mendatar ? batang.x : batang.y);
                if (mendatar) {
                    ctx.fillText(teks, posisiNilai + 4, posisiItem);
                } else {
                    ctx.fillText(teks, posisiItem, posisiNilai - 4);
                }
            });
            ctx.restore();
        },
    };
}

/**
 * Memecah teks panjang (mis. deskripsi CPL) menjadi beberapa baris supaya tooltip tidak terlalu lebar.
 */
function pecahBaris(teks, panjangMaksimum) {
    const daftarBaris = [];
    let baris = '';
    String(teks ?? '').split(' ').forEach((kata) => {
        if (baris !== '' && `${baris} ${kata}`.length > panjangMaksimum) {
            daftarBaris.push(baris);
            baris = kata;
        } else {
            baris = baris === '' ? kata : `${baris} ${kata}`;
        }
    });
    if (baris !== '') {
        daftarBaris.push(baris);
    }

    return daftarBaris;
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
            plugins: [pluginLabelAngka(data.nilai, mendatar)],
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

/*
 * Dashboard Mahasiswa (KD-74): grafik batang capaian CPL kumulatif dan grafik garis perkembangan
 * CPL per periode. Mengeklik batang, titik garis, atau tombol kode CPL memilih satu CPL. Rinciannya
 * tampil di kartu Rincian (Blade, x-show), garisnya ditebalkan di grafik perkembangan, dan batangnya
 * diberi garis tepi.
 */
Alpine.data('dashboardMahasiswa', (data) => {
    // Objek Chart disimpan di luar data Alpine supaya tidak dibungkus proxy reaktif Alpine,
    // yang membuat Chart.js lambat dan bisa error.
    let grafikCpl = null;
    let grafikPerkembangan = null;
    const jumlahCpl = data.label.length;

    return {
        terpilih: data.terpilih,

        get kodeTerpilih() {
            return data.label[this.terpilih];
        },

        init() {
            if (this.$refs.kanvasCpl) {
                grafikCpl = this.buatGrafikCpl();
            }
            if (this.$refs.kanvasPerkembangan) {
                grafikPerkembangan = this.buatGrafikPerkembangan();
            }
        },

        pilih(indeks) {
            this.terpilih = indeks;
            grafikCpl?.update();
            if (grafikPerkembangan) {
                this.tandaiGarisTerpilih(grafikPerkembangan);
                grafikPerkembangan.update();
            }
        },

        teksStatus(indeks) {
            if (data.nilai[indeks] === null) {
                return 'Belum ada nilai';
            }

            return { lulus: 'Lulus', belum: 'Belum Lulus', netral: 'Ambang belum ditetapkan' }[data.status[indeks]];
        },

        /*
         * Batang hijau jika Lulus, merah jika Belum Lulus, dan biru jika ambang belum ditetapkan
         * (bagian 7.2). Di layar kecil dengan banyak CPL, batang dibuat mendatar seperti grafik laporan.
         */
        buatGrafikCpl() {
            const komponen = this;
            const warnaStatus = { lulus: warna('success-chart'), belum: warna('danger-chart'), netral: warna('primary-700'), kosong: warna('neutral') };
            const mendatar = window.matchMedia('(max-width: 767px)').matches && jumlahCpl > 6;
            const sumbuNilai = mendatar ? 'x' : 'y';
            const sumbuItem = mendatar ? 'y' : 'x';

            if (mendatar) {
                this.$refs.kanvasCpl.parentElement.style.height = `${Math.max(240, jumlahCpl * 44)}px`;
            }

            const daftarDataset = [{
                type: 'bar',
                label: 'Capaian CPL',
                data: data.nilai,
                backgroundColor: data.status.map((status) => warnaStatus[status]),
                // CPL terpilih diberi garis tepi gelap.
                borderColor: warna('text'),
                borderWidth: (konteks) => (konteks.dataIndex === komponen.terpilih ? 2 : 0),
                borderRadius: 4,
                maxBarThickness: 40,
            }];

            if (data.ambang !== null) {
                daftarDataset.push({
                    type: 'line',
                    label: `Ambang Batas Kelulusan Mahasiswa (${formatAngka(data.ambang)})`,
                    data: data.label.map(() => data.ambang),
                    borderColor: warna('text-muted'),
                    borderDash: [6, 4],
                    borderWidth: 1.5,
                    pointRadius: 0,
                    pointHoverRadius: 0,
                });
            }

            return new Chart(this.$refs.kanvasCpl, {
                data: { labels: data.label, datasets: daftarDataset },
                options: {
                    indexAxis: mendatar ? 'y' : 'x',
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: { padding: { top: 18, right: mendatar ? 48 : 0 } },
                    // Tooltip dan klik berlaku di seluruh kolom satu CPL, tidak harus tepat di batangnya.
                    interaction: { mode: 'index', intersect: false, axis: sumbuItem },
                    onClick: (event, daftarElemen) => {
                        if (daftarElemen.length > 0) {
                            komponen.pilih(daftarElemen[0].index);
                        }
                    },
                    onHover: (event, daftarElemen) => {
                        event.native.target.style.cursor = daftarElemen.length > 0 ? 'pointer' : 'default';
                    },
                    scales: {
                        [sumbuNilai]: { min: 0, max: 100, ticks: { callback: (nilai) => `${nilai}%` } },
                        [sumbuItem]: {
                            ticks: {
                                autoSkip: false,
                                // Kode CPL terpilih ditebalkan.
                                font: (konteks) => ({ size: 11, weight: konteks.index === komponen.terpilih ? '700' : '400' }),
                            },
                        },
                    },
                    plugins: {
                        // Penanda legenda berupa garis putus-putus, sama dengan garis ambang di grafik.
                        legend: { display: data.ambang !== null, position: 'bottom', labels: { usePointStyle: true, pointStyle: 'line', filter: (item) => item.datasetIndex === 1 } },
                        tooltip: {
                            callbacks: {
                                title: (daftarItem) => data.label[daftarItem[0].dataIndex],
                                afterTitle: (daftarItem) => pecahBaris(data.deskripsi[daftarItem[0].dataIndex], 48),
                                label: (konteks) => {
                                    if (konteks.datasetIndex !== 0) {
                                        return konteks.dataset.label;
                                    }
                                    // Kolom CPL tanpa nilai tetap ikut tooltip (mode index), nilainya null.
                                    const nilai = konteks.parsed[sumbuNilai];

                                    return nilai === null ? 'Capaian: belum ada nilai' : `Capaian: ${formatAngka(nilai)}%`;
                                },
                                footer: (daftarItem) => `Status: ${komponen.teksStatus(daftarItem[0].dataIndex)}`,
                            },
                        },
                    },
                },
                plugins: [pluginLabelAngka(data.nilai, mendatar, '–')],
            });
        },

        /*
         * Satu garis per CPL. CPL terpilih biru dan tebal, CPL lain abu tipis sebagai pembanding,
         * supaya grafik tetap terbaca walaupun CPL-nya banyak.
         */
        buatGrafikPerkembangan() {
            const komponen = this;
            const daftarDataset = data.label.map((kode, indeks) => ({
                label: kode,
                data: data.perkembangan[indeks],
                pointBorderColor: warna('surface'),
                pointBorderWidth: 2,
                pointHoverRadius: 7,
                // Hit area titik lebih besar dari titiknya supaya mudah disentuh.
                pointHitRadius: 12,
            }));

            if (data.ambang !== null) {
                daftarDataset.push({
                    label: `Ambang Batas Kelulusan Mahasiswa (${formatAngka(data.ambang)})`,
                    data: data.periode.map(() => data.ambang),
                    borderColor: warna('text-muted'),
                    borderDash: [6, 4],
                    borderWidth: 1.5,
                    pointRadius: 0,
                    pointHoverRadius: 0,
                    pointHitRadius: 0,
                    order: 2,
                });
            }

            const grafik = new Chart(this.$refs.kanvasPerkembangan, {
                type: 'line',
                data: { labels: data.periode, datasets: daftarDataset },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: { padding: { top: 8, right: 8 } },
                    interaction: { mode: 'nearest', intersect: false },
                    onClick: (event, daftarElemen) => {
                        const titik = daftarElemen.find((elemen) => elemen.datasetIndex < jumlahCpl);
                        if (titik) {
                            komponen.pilih(titik.datasetIndex);
                        }
                    },
                    onHover: (event, daftarElemen) => {
                        event.native.target.style.cursor = daftarElemen.some((elemen) => elemen.datasetIndex < jumlahCpl) ? 'pointer' : 'default';
                    },
                    scales: {
                        // offset: titik periode pertama dan terakhir tidak menempel di tepi grafik.
                        x: { offset: true },
                        y: { min: 0, max: 100, ticks: { callback: (nilai) => `${nilai}%` } },
                    },
                    plugins: {
                        legend: { display: data.ambang !== null, position: 'bottom', labels: { usePointStyle: true, pointStyle: 'line', filter: (item) => item.datasetIndex === jumlahCpl } },
                        tooltip: {
                            filter: (item) => item.datasetIndex < jumlahCpl,
                            callbacks: { label: (konteks) => `${konteks.dataset.label}: ${formatAngka(konteks.parsed.y)}%` },
                        },
                    },
                },
            });
            this.tandaiGarisTerpilih(grafik);
            grafik.update('none');

            return grafik;
        },

        tandaiGarisTerpilih(grafik) {
            grafik.data.datasets.slice(0, jumlahCpl).forEach((dataset, indeks) => {
                const dipilih = indeks === this.terpilih;
                const warnaGaris = dipilih ? warna('primary-700') : `${warna('neutral')}59`;
                dataset.borderColor = warnaGaris;
                dataset.backgroundColor = warnaGaris;
                dataset.pointBackgroundColor = warnaGaris;
                dataset.borderWidth = dipilih ? 2.5 : 1.5;
                dataset.pointRadius = dipilih ? 5 : 3;
                // Angka order lebih kecil digambar paling atas.
                dataset.order = dipilih ? 0 : 1;
            });
        },
    };
});

window.Alpine = Alpine;
Alpine.start();
