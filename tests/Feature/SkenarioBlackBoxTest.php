<?php

namespace Tests\Feature;

use App\Models\Cpl;
use App\Models\Dosen;
use App\Models\KelasKuliah;
use App\Models\Kurikulum;
use App\Models\Mahasiswa;
use App\Models\MataKuliah;
use App\Models\MetodePenilaian;
use App\Models\PeriodeAkademik;
use App\Models\ProfilLulusan;
use App\Models\RencanaAsesmen;
use App\Models\Role;
use App\Models\SubCpmk;
use App\Models\User;
use App\Services\MatriksPemetaan;
use App\Support\Angka;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Pengujian black box (bagian 12 spesifikasi): satu test untuk setiap skenario, dengan langkah dan
 * data uji yang sama dengan tabel di docs/pengujian/black-box.md.
 *
 * Semua test memakai data demo dari seeder, jadi setiap skenario bisa diulang secara manual di
 * aplikasi setelah `php artisan migrate:fresh --seed`. Kasus tepi setiap fitur diuji di file test
 * fiturnya masing-masing.
 */
class SkenarioBlackBoxTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        // Nilai ambang sama dengan .env.example, ditulis di sini agar hasil test tidak bergantung pada .env.
        config(['capaian.ambang_kelulusan_mahasiswa' => 50.01, 'capaian.ambang_ketercapaian' => 85]);
    }

    public function test_skenario_01_login_dengan_data_valid_diarahkan_ke_halaman_sesuai_hak_akses(): void
    {
        $daftarAkun = [
            ['admin@simonev.test', 'admin123', 'admin.beranda'],
            ['koprodi@simonev.test', 'koprodi123', 'koprodi.dashboard'],
            ['9990000001', 'dosen123', 'dosen.dashboard'],
            ['9992300001', 'mahasiswa123', 'mahasiswa.capaian'],
        ];

        foreach ($daftarAkun as [$identitas, $password, $halamanUtama]) {
            $this->post(route('login.store'), ['identitas' => $identitas, 'password' => $password])
                ->assertRedirect(route('peran-aktif.create'));
            // Akun berperan satu langsung diarahkan ke halaman utama perannya.
            $this->get(route('peran-aktif.create'))->assertRedirect(route('beranda'));
            $this->get(route('beranda'))->assertRedirect(route($halamanUtama));
            $this->get(route($halamanUtama))->assertOk();

            $this->post(route('logout'));
        }

        // Akun berperan dua (Koprodi dan Dosen) memilih peran dulu.
        $this->post(route('login.store'), ['identitas' => 'koprodi.dosen@simonev.test', 'password' => 'koprodi.dosen123']);
        $this->get(route('peran-aktif.create'))->assertOk()->assertSee('Pilih Peran')->assertSee('Koprodi')->assertSee('Dosen');
        $this->post(route('peran-aktif.store'), ['role_id' => Role::ambil(Role::DOSEN)->role_id])->assertRedirect(route('beranda'));
        $this->get(route('beranda'))->assertRedirect(route('dosen.dashboard'));
    }

    public function test_skenario_02_login_dengan_data_salah_ditolak_dengan_pesan(): void
    {
        // Mengikuti pengalihan seperti peramban: kembali ke halaman login dengan pesan kesalahan.
        $this->followingRedirects()
            ->from(route('login'))
            ->post(route('login.store'), ['identitas' => 'admin@simonev.test', 'password' => 'salah123'])
            ->assertOk()
            ->assertSee('Masuk ke Sistem')
            ->assertSee('Email/NPM/NIDN atau password salah.');

        $this->assertGuest();
    }

    public function test_skenario_03_membuka_fitur_di_luar_kewenangan_ditolak(): void
    {
        // Tamu yang belum login diarahkan ke halaman login.
        $this->get(route('admin.beranda'))->assertRedirect(route('login'));

        $this->masukSebagaiAkunDemo('9990000001', Role::DOSEN);
        $this->get(route('admin.beranda'))->assertForbidden()->assertSee('Akses Ditolak');
        $this->get(route('koprodi.laporan'))->assertForbidden();
        // INF101 A diampu dosen lain (NIDN 9990000004).
        $this->get(route('dosen.kelas.rencana-asesmen.index', $this->kelas('INF101', 'A')))->assertForbidden();

        $this->masukSebagaiAkunDemo('9992300001', Role::MAHASISWA);
        $this->get(route('dosen.nilai.edit'))->assertForbidden();
        $this->get(route('koprodi.dashboard'))->assertForbidden();
    }

    public function test_skenario_04_admin_menambahkan_data_master_kurikulum(): void
    {
        $kurikulum = $this->kurikulum(2026);
        $this->masukSebagaiAkunDemo('admin@simonev.test', Role::ADMIN);

        $this->post(route('admin.cpl.store'), [
            'kurikulum_id' => $kurikulum->kurikulum_id,
            'kode_cpl' => 'CPL06',
            'kategori_cpl' => 'Keterampilan Khusus',
            'deskripsi_cpl' => 'Mampu menerapkan prinsip keamanan informasi dalam pengembangan perangkat lunak.',
        ])->assertRedirect(route('admin.cpl.index'))
            ->assertSessionHas('sukses', 'CPL berhasil ditambahkan.');

        $this->assertDatabaseHas('cpl', ['kurikulum_id' => $kurikulum->kurikulum_id, 'kode_cpl' => 'CPL06', 'kategori_cpl' => 'Keterampilan Khusus']);
        $this->get(route('admin.cpl.index', ['kurikulum' => $kurikulum->kurikulum_id]))
            ->assertSee('CPL06')
            ->assertSee('Mampu menerapkan prinsip keamanan informasi dalam pengembangan perangkat lunak.');
    }

    public function test_skenario_05_admin_menyimpan_pemetaan_kurikulum(): void
    {
        $kurikulum = $this->kurikulum(2026);
        $daftarCpl = Cpl::with('profilLulusan')->where('kurikulum_id', $kurikulum->kurikulum_id)->get()->keyBy('kode_cpl');
        $pl02 = ProfilLulusan::where('kurikulum_id', $kurikulum->kurikulum_id)->where('kode_pl', 'PL02')->firstOrFail();
        $this->masukSebagaiAkunDemo('admin@simonev.test', Role::ADMIN);

        // Form matriks mengirim semua lingkaran yang tercentang: hubungan yang sudah ada ditambah CPL04–PL02.
        $terhubung = $daftarCpl
            ->flatMap(fn (Cpl $cpl) => $cpl->profilLulusan->map(fn (ProfilLulusan $pl) => MatriksPemetaan::kunci($cpl->cpl_id, $pl->profil_lulusan_id)))
            ->push(MatriksPemetaan::kunci($daftarCpl['CPL04']->cpl_id, $pl02->profil_lulusan_id))
            ->all();

        $this->put(route('admin.pemetaan.update'), ['jenis' => 'cpl-pl', 'kurikulum_id' => $kurikulum->kurikulum_id, 'terhubung' => $terhubung])
            ->assertRedirect(route('admin.pemetaan.edit', ['jenis' => 'cpl-pl', 'kurikulum' => $kurikulum->kurikulum_id]))
            ->assertSessionHas('sukses', 'Pemetaan CPL – Profil Lulusan berhasil disimpan.');

        $this->assertSame(['PL01', 'PL02'], $daftarCpl['CPL04']->profilLulusan()->orderBy('kode_pl')->pluck('kode_pl')->all());
        // Hubungan lain tidak berubah.
        $this->assertSame(['PL01', 'PL02', 'PL03'], $daftarCpl['CPL01']->profilLulusan()->orderBy('kode_pl')->pluck('kode_pl')->all());
    }

    public function test_skenario_06_admin_membuat_kelas_dan_menentukan_dosen_serta_peserta(): void
    {
        $dosenDemo = Dosen::where('nidn', '9990000001')->firstOrFail();
        $this->masukSebagaiAkunDemo('admin@simonev.test', Role::ADMIN);

        $this->post(route('admin.kelas-kuliah.store'), [
            'periode_akademik_id' => PeriodeAkademik::aktif()->periode_akademik_id,
            'mata_kuliah_id' => $this->mataKuliah('INF102')->mata_kuliah_id,
            'nama_kelas' => 'B',
        ])->assertSessionHas('sukses', 'Kelas berhasil ditambahkan. Atur dosen pengampu dan pesertanya.');
        $kelas = $this->kelas('INF102', 'B');

        $this->post(route('admin.kelas-kuliah.dosen.store', $kelas), ['dosen_id' => $dosenDemo->dosen_id])
            ->assertSessionHas('sukses', 'Dosen pengampu berhasil ditambahkan.');
        $this->post(route('admin.kelas-kuliah.peserta.store', $kelas), ['mahasiswa_id' => Mahasiswa::where('nim', '9992300001')->value('mahasiswa_id')])
            ->assertSessionHas('sukses', 'Peserta berhasil ditambahkan.');
        $this->post(route('admin.kelas-kuliah.peserta-angkatan.store', $kelas), ['angkatan' => 2024])
            ->assertSessionHas('sukses', '8 mahasiswa angkatan 2024 ditambahkan.');

        $this->assertSame(['9990000001'], $kelas->dosen()->pluck('nidn')->all());
        $this->assertSame(9, $kelas->pesertaKelas()->count());
        $this->get(route('admin.kelas-kuliah.show', $kelas))
            ->assertOk()
            ->assertSee($dosenDemo->nama)
            ->assertSee('Mahasiswa Demo')
            ->assertSee('Hendra Kurniawan');
    }

    public function test_skenario_07_dosen_menambahkan_rencana_asesmen_pada_kelas_yang_diampu(): void
    {
        $kelas = $this->kelasHasilSkenario6();
        $this->masukSebagaiAkunDemo('9990000001', Role::DOSEN);

        $this->post(route('dosen.kelas.rencana-asesmen.store', $kelas), [
            'metode_penilaian_id' => $this->metodePenilaian('KUIS'),
            'nama_asesmen' => 'Kuis 1',
            'bobot_asesmen' => '10',
            'nilai_maksimum' => '20',
            'tanggal_asesmen' => '2026-09-14',
            'kontribusi' => [$this->subCpmkInf102('Sub-CPMK0411') => '10'],
        ])->assertRedirect(route('dosen.kelas.rencana-asesmen.index', $kelas))
            ->assertSessionHas('sukses', 'Rencana asesmen berhasil ditambahkan.');

        $this->assertDatabaseHas('rencana_asesmen', ['kelas_kuliah_id' => $kelas->kelas_kuliah_id, 'nama_asesmen' => 'Kuis 1', 'bobot_asesmen' => 10, 'nilai_maksimum' => 20]);
        $this->get(route('dosen.kelas.rencana-asesmen.index', $kelas))->assertSee('Kuis 1');
    }

    public function test_skenario_08_dosen_menghubungkan_asesmen_dengan_sub_cpmk(): void
    {
        $kelas = $this->kelasHasilSkenario6();
        $this->masukSebagaiAkunDemo('9990000001', Role::DOSEN);

        // UTS (bobot 30) mengukur dua Sub-CPMK dengan kontribusi 10 dan 20.
        $this->post(route('dosen.kelas.rencana-asesmen.store', $kelas), [
            'metode_penilaian_id' => $this->metodePenilaian('UTS'),
            'nama_asesmen' => 'UTS',
            'bobot_asesmen' => '30',
            'nilai_maksimum' => '100',
            'tanggal_asesmen' => '2026-10-26',
            'kontribusi' => [$this->subCpmkInf102('Sub-CPMK0411') => '10', $this->subCpmkInf102('Sub-CPMK0412') => '20'],
        ])->assertSessionHasNoErrors();

        $uts = RencanaAsesmen::where('kelas_kuliah_id', $kelas->kelas_kuliah_id)->where('nama_asesmen', 'UTS')->firstOrFail();
        $this->assertSame(
            ['Sub-CPMK0411' => '10.00', 'Sub-CPMK0412' => '20.00'],
            $uts->subCpmk->mapWithKeys(fn (SubCpmk $subCpmk) => [$subCpmk->kode_sub_cpmk => $subCpmk->pivot->bobot_kontribusi])->sortKeys()->all(),
        );
        $this->get(route('dosen.kelas.rencana-asesmen.index', $kelas))
            ->assertSee('Sub-CPMK0411 (10)')
            ->assertSee('Sub-CPMK0412 (20)');
    }

    public function test_skenario_09_dosen_memasukkan_nilai_peserta_kelas(): void
    {
        $kelas = $this->kelas('INF102', 'A');
        $uts = $kelas->rencanaAsesmen()->where('nama_asesmen', 'UTS')->firstOrFail();
        $this->masukSebagaiAkunDemo('9990000001', Role::DOSEN);

        // Data demo sengaja mengosongkan nilai UTS dua peserta pertama.
        $this->get(route('dosen.nilai.edit', ['kelas' => $kelas->kelas_kuliah_id, 'asesmen' => $uts->rencana_asesmen_id]))
            ->assertOk()
            ->assertSee('Putra Wijaya')
            ->assertSee('Qori Amalia');

        // Form mengirim nilai semua peserta: dua nilai baru, sisanya nilai yang sudah ada.
        $nilaiBaru = ['9992600001' => '78', '9992600002' => '85,5'];
        $nilai = $kelas->pesertaKelas()->with('mahasiswa')->get()->mapWithKeys(fn ($peserta) => [
            $peserta->peserta_kelas_id => $nilaiBaru[$peserta->mahasiswa->nim] ?? $this->nilai($uts, $peserta->mahasiswa->nim),
        ])->all();

        $this->put(route('dosen.nilai.update', $uts), ['nilai' => $nilai])
            ->assertSessionHas('sukses', 'Nilai UTS berhasil disimpan: 8 terisi, 0 belum diinput.');

        $this->assertSame('78.00', $this->nilai($uts, '9992600001'));
        $this->assertSame('85.50', $this->nilai($uts, '9992600002'));
    }

    public function test_skenario_10_nilai_di_atas_nilai_maksimum_ditolak(): void
    {
        $kelas = $this->kelas('INF102', 'A');
        $kuis = $kelas->rencanaAsesmen()->where('nama_asesmen', 'Kuis 1')->firstOrFail();
        $peserta = $kelas->pesertaKelas()->whereRelation('mahasiswa', 'nim', '9992600001')->firstOrFail();
        $nilaiLama = $this->nilai($kuis, '9992600001');
        $halamanInput = route('dosen.nilai.edit', ['kelas' => $kelas->kelas_kuliah_id, 'asesmen' => $kuis->rencana_asesmen_id]);
        $this->masukSebagaiAkunDemo('9990000001', Role::DOSEN);

        // Nilai maksimum Kuis 1 adalah 20. Mengikuti pengalihan seperti peramban: kembali ke
        // halaman input nilai dengan pesan validasi di bawah field.
        $this->followingRedirects()
            ->from($halamanInput)
            ->put(route('dosen.nilai.update', $kuis), ['nilai' => [$peserta->peserta_kelas_id => '25']])
            ->assertOk()
            ->assertSee('Nilai tidak boleh lebih dari nilai maksimum (20).');

        $this->assertSame($nilaiLama, $this->nilai($kuis, '9992600001'));
    }

    public function test_skenario_11_capaian_terhitung_jika_nilai_asesmen_lengkap(): void
    {
        // Semua asesmen INF201 A (periode 2025/2026 Genap) sudah dinilai untuk 8 peserta.
        $filter = [
            'kurikulum' => $this->kurikulum(2021)->kurikulum_id,
            'periode' => PeriodeAkademik::where('tahun_akademik', '2025/2026')->value('periode_akademik_id'),
            'mata_kuliah' => $this->mataKuliah('INF201')->mata_kuliah_id,
        ];
        $this->masukSebagaiAkunDemo('koprodi@simonev.test', Role::KOPRODI);

        $this->assertSame(
            ['Sub-CPMK0121' => '67,38', 'Sub-CPMK0211' => '67,42', 'Sub-CPMK0212' => '67,62'],
            $this->hasilLaporan($this->get(route('koprodi.laporan', [...$filter, 'tab' => 'sub-cpmk']))),
        );
        $this->assertSame(
            ['CPMK012' => '67,38', 'CPMK021' => '67,52'],
            $this->hasilLaporan($this->get(route('koprodi.laporan', [...$filter, 'tab' => 'cpmk']))),
        );
        // CPL03 dan CPL04 tidak diukur INF201, jadi belum punya nilai.
        $this->assertSame(
            ['CPL01' => '67,38', 'CPL02' => '67,52', 'CPL03' => 'Belum ada nilai', 'CPL04' => 'Belum ada nilai'],
            $this->hasilLaporan($this->get(route('koprodi.laporan', [...$filter, 'tab' => 'cpl']))),
        );
    }

    public function test_skenario_12_koprodi_membuka_dashboard_dan_ringkasan_capaian_tampil(): void
    {
        $this->masukSebagaiAkunDemo('koprodi@simonev.test', Role::KOPRODI);

        $response = $this->get(route('koprodi.dashboard'))
            ->assertOk()
            ->assertSee('Kurikulum OBE 2026')
            ->assertSee('Ringkasan Capaian CPL')
            // Data grafik donut: Tercapai, Tidak Tercapai, Belum ada nilai.
            ->assertSee('[3,1,1]', false);

        // Kartu statistik: 24 mahasiswa; Kurikulum OBE 2026 punya 3 MK, 10 CPMK, 5 CPL; 2 kelas di periode aktif.
        $this->assertSame(['mahasiswa' => 24, 'mata_kuliah' => 3, 'kelas_aktif' => 2, 'cpmk' => 10, 'cpl' => 5], $response->viewData('jumlah'));
        $this->assertSame(['tercapai' => 3, 'tidak_tercapai' => 1, 'belum_ada_nilai' => 1, 'total' => 5], $response->viewData('ringkasan'));
    }

    public function test_skenario_13_laporan_tampil_sesuai_filter(): void
    {
        $filter = [
            'kurikulum' => $this->kurikulum(2026)->kurikulum_id,
            'periode' => PeriodeAkademik::aktif()->periode_akademik_id,
            'tab' => 'cpmk',
        ];
        $this->masukSebagaiAkunDemo('koprodi@simonev.test', Role::KOPRODI);

        // Tanpa filter mata kuliah: CPMK dari INF101 dan INF102.
        $this->assertSame(
            ['CPMK011 · INF102', 'CPMK021 · INF101', 'CPMK022 · INF101', 'CPMK031 · INF101', 'CPMK041 · INF102', 'CPMK042 · INF102'],
            array_keys($this->hasilLaporan($this->get(route('koprodi.laporan', $filter)))),
        );
        // Filter mata kuliah INF101: hanya CPMK INF101.
        $this->assertSame(
            ['CPMK021' => '69,97', 'CPMK022' => '70,06', 'CPMK031' => '69,78'],
            $this->hasilLaporan($this->get(route('koprodi.laporan', [...$filter, 'mata_kuliah' => $this->mataKuliah('INF101')->mata_kuliah_id]))),
        );
        // Filter mahasiswa: hanya nilai Mahasiswa Demo, yang mengikuti INF101 saja di kurikulum ini.
        $this->assertSame(
            ['CPMK021 · INF101' => '85,25', 'CPMK022 · INF101' => '91,71', 'CPMK031 · INF101' => '93,00'],
            $this->hasilLaporan($this->get(route('koprodi.laporan', [...$filter, 'mahasiswa' => Mahasiswa::where('nim', '9992300001')->value('mahasiswa_id')]))),
        );
    }

    public function test_skenario_14_dosen_membuka_laporan_kelas_yang_diampu(): void
    {
        $this->masukSebagaiAkunDemo('9990000001', Role::DOSEN);

        // Bawaan Kurikulum OBE 2026: Dosen Demo hanya mengampu INF102 A di kurikulum ini.
        $this->assertSame(
            ['CPMK011 · INF102' => '67,38', 'CPMK041 · INF102' => '66,69', 'CPMK042 · INF102' => '67,62'],
            $this->hasilLaporan($this->get(route('dosen.laporan', ['tab' => 'cpmk']))),
        );
        // Tab CPL hanya berisi CPL yang didukung INF102 (KD-46).
        $this->assertSame(
            ['CPL01' => '67,38', 'CPL04' => '67,49'],
            $this->hasilLaporan($this->get(route('dosen.laporan', ['tab' => 'cpl']))),
        );
        // Kelas INF101 A milik dosen lain tidak terbuka walaupun id-nya diketik di alamat.
        $kelasLain = $this->kelas('INF101', 'A');
        $this->assertSame(
            ['CPMK011 · INF102', 'CPMK041 · INF102', 'CPMK042 · INF102'],
            array_keys($this->hasilLaporan($this->get(route('dosen.laporan', ['tab' => 'cpmk', 'kelas' => $kelasLain->kelas_kuliah_id])))),
        );
    }

    public function test_skenario_15_mahasiswa_hanya_melihat_nilai_dan_capaian_miliknya(): void
    {
        $this->masukSebagaiAkunDemo('9992300001', Role::MAHASISWA);

        // Bawaan: periode 2026/2027 Ganjil, mata kuliah INF101 Matematika Diskrit.
        $this->get(route('mahasiswa.capaian'))
            ->assertOk()
            ->assertSee('Mahasiswa Demo')
            ->assertSee('NPM 9992300001')
            ->assertSeeInOrder(['Kuis 1', '41,5', 'dari 50', 'Tugas 1', '90', 'UTS', '86', 'UAS', '93'])
            ->assertSeeInOrder(['Rata-rata Sub-CPMK', '89,30', 'Rata-rata CPMK', '89,99', 'Rata-rata CPL Kumulatif', '90,63'])
            // Teman sekelas di INF101 A tidak tampil.
            ->assertDontSee('Putra Wijaya')
            ->assertDontSee('9992600001');

        // Sebaliknya, mahasiswa lain tidak melihat data Mahasiswa Demo.
        $this->masukSebagaiAkunDemo('9992600001', Role::MAHASISWA);
        $this->get(route('mahasiswa.capaian'))->assertSee('Putra Wijaya')->assertDontSee('Mahasiswa Demo');
    }

    /**
     * Bagian sisi server dari skenario 16: setiap halaman bisa dibuka dan memuat meta viewport
     * untuk layar mobile. Tampilan di lebar 360 px (tanpa geser horizontal) diperiksa di Chrome
     * dengan `npm run cek:tampilan`, karena test PHP tidak menggambar halaman.
     */
    public function test_skenario_16_semua_halaman_bisa_dibuka_dan_siap_untuk_layar_mobile(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('<meta name="viewport" content="width=device-width, initial-scale=1">', false);

        foreach ($this->daftarHalamanPerAkun() as [$identitas, $kodeRole, $daftarAlamat]) {
            $this->masukSebagaiAkunDemo($identitas, $kodeRole);

            foreach ($daftarAlamat as $alamat) {
                $this->get($alamat)->assertOk()->assertSee('<meta name="viewport" content="width=device-width, initial-scale=1">', false);
            }
        }
    }

    /**
     * Halaman yang dibuka setiap akun demo, sama dengan daftar di scripts/cek-tampilan.mjs.
     *
     * @return list<array{string, string, list<string>}>
     */
    private function daftarHalamanPerAkun(): array
    {
        $kelasInf102 = $this->kelas('INF102', 'A');
        $utsInf102 = $kelasInf102->rencanaAsesmen()->where('nama_asesmen', 'UTS')->firstOrFail();

        return [
            ['admin@simonev.test', Role::ADMIN, [
                route('admin.beranda'),
                ...array_map(fn (string $nama) => route("admin.{$nama}.index"), [
                    'program-studi', 'kurikulum', 'evaluasi-kurikulum', 'profil-lulusan', 'profesi', 'cpl', 'bahan-kajian',
                    'mata-kuliah', 'cpmk', 'sub-cpmk', 'metode-penilaian', 'dosen', 'mahasiswa', 'periode-akademik', 'kelas-kuliah', 'akun',
                ]),
                route('admin.profil-lulusan.create'),
                route('admin.cpl.create'),
                route('admin.kelas-kuliah.create'),
                route('admin.akun.create'),
                route('admin.mata-kuliah.show', $this->mataKuliah('INF101')),
                route('admin.sub-cpmk.show', SubCpmk::firstOrFail()),
                route('admin.kelas-kuliah.show', $kelasInf102),
                ...array_map(fn (string $jenis) => route('admin.pemetaan.edit', ['jenis' => $jenis]), ['cpl-pl', 'cpl-bk', 'bk-mk', 'cpmk-mk']),
                route('profil'),
                route('password.edit'),
            ]],
            ['koprodi@simonev.test', Role::KOPRODI, [
                route('koprodi.dashboard'),
                ...array_map(fn (string $tab) => route('koprodi.kurikulum', ['tab' => $tab]), ['ringkasan', 'mata-kuliah', 'pemetaan']),
                ...array_map(fn (string $tab) => route('koprodi.laporan', ['tab' => $tab]), ['cpl', 'cpmk', 'sub-cpmk']),
            ]],
            ['9990000001', Role::DOSEN, [
                route('dosen.dashboard'),
                route('dosen.mata-kuliah.index'),
                route('dosen.mata-kuliah.show', $this->mataKuliah('INF102')),
                route('dosen.rencana-asesmen'),
                route('dosen.kelas.rencana-asesmen.index', $kelasInf102),
                route('dosen.kelas.rencana-asesmen.create', $kelasInf102),
                route('dosen.nilai.edit'),
                route('dosen.nilai.edit', ['kelas' => $kelasInf102->kelas_kuliah_id, 'asesmen' => $utsInf102->rencana_asesmen_id]),
                route('dosen.laporan'),
            ]],
            ['9992300001', Role::MAHASISWA, [route('mahasiswa.capaian')]],
        ];
    }

    /** Login sebagai akun demo (bagian 10 spesifikasi) dengan peran aktif tertentu. */
    private function masukSebagaiAkunDemo(string $identitas, string $kodeRole): void
    {
        $this->masukSebagai($kodeRole, User::cariBerdasarkanIdentitas($identitas));
    }

    /**
     * Keadaan setelah skenario 6: kelas INF102 B pada periode aktif yang diampu Dosen Demo dan
     * belum punya rencana asesmen.
     */
    private function kelasHasilSkenario6(): KelasKuliah
    {
        $kelas = KelasKuliah::factory()->create([
            'periode_akademik_id' => PeriodeAkademik::aktif()->periode_akademik_id,
            'mata_kuliah_id' => $this->mataKuliah('INF102')->mata_kuliah_id,
            'nama_kelas' => 'B',
        ]);
        $kelas->dosen()->attach(Dosen::where('nidn', '9990000001')->firstOrFail());

        return $kelas;
    }

    /**
     * Isi tabel hasil Laporan Capaian: kode item => rata-rata capaian seperti yang tampil.
     *
     * @return array<string, string>
     */
    private function hasilLaporan(TestResponse $response): array
    {
        return collect($response->assertOk()->viewData('hasil')['baris'])
            ->mapWithKeys(fn (array $baris) => [
                $baris['kode'] => $baris['rekap']['rata_rata'] === null ? 'Belum ada nilai' : Angka::format($baris['rekap']['rata_rata']),
            ])
            ->all();
    }

    private function kurikulum(int $tahunBerlaku): Kurikulum
    {
        return Kurikulum::where('tahun_berlaku', $tahunBerlaku)->firstOrFail();
    }

    private function mataKuliah(string $kode): MataKuliah
    {
        return MataKuliah::where('kode_mata_kuliah', $kode)->firstOrFail();
    }

    private function kelas(string $kodeMataKuliah, string $namaKelas): KelasKuliah
    {
        return KelasKuliah::where('nama_kelas', $namaKelas)->whereRelation('mataKuliah', 'kode_mata_kuliah', $kodeMataKuliah)->firstOrFail();
    }

    private function metodePenilaian(string $kode): string
    {
        return MetodePenilaian::where('kode_metode', $kode)->value('metode_penilaian_id');
    }

    /** Sub-CPMK0411 dan Sub-CPMK0412 ada di dua kurikulum, jadi dicari lewat mata kuliah INF102. */
    private function subCpmkInf102(string $kode): string
    {
        return SubCpmk::where('kode_sub_cpmk', $kode)
            ->whereRelation('pemetaanCpmkMk.mataKuliah', 'kode_mata_kuliah', 'INF102')
            ->value('sub_cpmk_id');
    }

    private function nilai(RencanaAsesmen $rencanaAsesmen, string $nim): ?string
    {
        return $rencanaAsesmen->nilaiAsesmen()->whereRelation('pesertaKelas.mahasiswa', 'nim', $nim)->value('nilai');
    }
}
