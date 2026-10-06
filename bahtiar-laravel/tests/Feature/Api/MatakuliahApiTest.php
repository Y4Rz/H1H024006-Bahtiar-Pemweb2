<?php

namespace Tests\Feature\Api;

use App\Models\Mahasiswa;
use App\Models\Matakuliah;
use App\Models\ProgramStudi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MatakuliahApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::factory()->create(['peran' => 'admin']);
        Sanctum::actingAs($admin, ['mahasiswa:baca', 'mahasiswa:tulis']);

        Matakuliah::create(['kode' => 'TKO101', 'nama' => 'Pemrograman Web II', 'sks' => 3, 'semester' => 4]);
        Matakuliah::create(['kode' => 'TKO102', 'nama' => 'Struktur Data', 'sks' => 3, 'semester' => 2]);
        Matakuliah::create(['kode' => 'TKO103', 'nama' => 'Sistem Operasi', 'sks' => 4, 'semester' => 3]);
    }

    public function test_daftar_matakuliah_terpaginasi(): void
    {
        $respons = $this->getJson('/api/matakuliah')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['data', 'links', 'meta'])
            ->assertJsonPath('meta.total', 3);

        $this->assertSame(
            ['id', 'kode', 'nama', 'sks', 'semester', 'jumlah_mahasiswa', 'dibuat_pada'],
            array_keys($respons->json('data.0'))
        );
    }

    public function test_daftar_matakuliah_dengan_filter(): void
    {
        $this->getJson('/api/matakuliah?semester=4')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.kode', 'TKO101');

        $this->getJson('/api/matakuliah?sks=3')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->getJson('/api/matakuliah?cari=Sistem&urut=kode&arah=desc')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nama', 'Sistem Operasi');
    }

    public function test_tampilkan_satu_matakuliah(): void
    {
        $mk = Matakuliah::first();

        $this->getJson("/api/matakuliah/{$mk->id}")
            ->assertOk()
            ->assertJsonPath('sukses', true)
            ->assertJsonPath('data.kode', 'TKO101')
            ->assertJsonPath('data.semester', 4);
    }

    public function test_matakuliah_tidak_ditemukan(): void
    {
        $this->getJson('/api/matakuliah/99999')
            ->assertStatus(404)
            ->assertExactJson(['sukses' => false, 'pesan' => 'Sumber daya tidak ditemukan']);
    }

    public function test_tambah_matakuliah(): void
    {
        $this->postJson('/api/matakuliah', [
            'kode' => 'TKO105',
            'nama' => 'Basis Data',
            'sks' => 4,
            'semester' => 4,
        ])
            ->assertStatus(201)
            ->assertJsonPath('sukses', true)
            ->assertJsonPath('pesan', 'Data mata kuliah berhasil dibuat')
            ->assertJsonPath('data.kode', 'TKO105')
            ->assertJsonPath('data.sks', 4);

        $this->assertDatabaseHas('matakuliahs', ['kode' => 'TKO105']);
    }

    public function test_tambah_matakuliah_gagal_validasi(): void
    {
        $this->postJson('/api/matakuliah', ['kode' => 'TKO101', 'nama' => '', 'sks' => 9, 'semester' => 0])
            ->assertStatus(422)
            ->assertJsonPath('sukses', false)
            ->assertJsonPath('galat.kode.0', 'Kode mata kuliah tersebut sudah terdaftar')
            ->assertJsonPath('galat.nama.0', 'Nama mata kuliah wajib diisi')
            ->assertJsonPath('galat.sks.0', 'SKS maksimal 6')
            ->assertJsonPath('galat.semester.0', 'Semester minimal 1');
    }

    public function test_ubah_matakuliah(): void
    {
        $mk = Matakuliah::first();

        $this->putJson("/api/matakuliah/{$mk->id}", ['sks' => 2])
            ->assertOk()
            ->assertJsonPath('sukses', true)
            ->assertJsonPath('pesan', 'Data mata kuliah berhasil diperbarui')
            ->assertJsonPath('data.sks', 2);

        $this->assertDatabaseHas('matakuliahs', ['id' => $mk->id, 'sks' => 2]);
    }

    public function test_ubah_matakuliah_gagal_validasi(): void
    {
        $mk = Matakuliah::first();

        $this->putJson("/api/matakuliah/{$mk->id}", ['semester' => 99])
            ->assertStatus(422)
            ->assertJsonPath('sukses', false)
            ->assertJsonStructure(['sukses', 'pesan', 'galat']);
    }

    public function test_hapus_matakuliah(): void
    {
        $mk = Matakuliah::first();

        $this->deleteJson("/api/matakuliah/{$mk->id}")
            ->assertOk()
            ->assertJsonPath('sukses', true)
            ->assertJsonPath('pesan', 'Data mata kuliah berhasil dihapus');

        $this->assertDatabaseMissing('matakuliahs', ['id' => $mk->id]);
        $this->getJson("/api/matakuliah/{$mk->id}")->assertStatus(404);
    }

    public function test_jumlah_mahasiswa_pada_matakuliah_terisi(): void
    {
        $mk = Matakuliah::first();

        $prodi = ProgramStudi::create(['kode' => 'TK', 'nama' => 'Teknik Komputer', 'jenjang' => 'S1']);
        $mhs = Mahasiswa::factory()->count(3)->create(['program_studi_id' => $prodi->id]);

        foreach ($mhs as $satu) {
            $satu->matakuliah()->attach($mk->id, ['nilai' => 'A']);
        }

        $this->getJson("/api/matakuliah/{$mk->id}")
            ->assertOk()
            ->assertJsonPath('data.jumlah_mahasiswa', 3);
    }
}
