<?php

namespace Tests\Feature\Api;

use App\Models\Mahasiswa;
use App\Models\ProgramStudi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MahasiswaApiTest extends TestCase
{
    use RefreshDatabase;

    private ProgramStudi $prodi;

    protected function setUp(): void
    {
        parent::setUp();

        $admin = User::factory()->create(['peran' => 'admin']);
        Sanctum::actingAs($admin, ['mahasiswa:baca', 'mahasiswa:tulis']);

        $this->prodi = ProgramStudi::create(['kode' => 'TK', 'nama' => 'Teknik Komputer', 'jenjang' => 'S1']);

        Mahasiswa::factory()->count(12)->create(['program_studi_id' => $this->prodi->id]);
    }

    public function test_endpoint_status_aktif(): void
    {
        $this->getJson('/api/status')
            ->assertOk()
            ->assertJsonPath('sukses', true)
            ->assertJsonPath('pesan', 'API Pemweb II aktif');
    }

    public function test_daftar_mahasiswa_terpaginasi(): void
    {
        $this->getJson('/api/mahasiswa')
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonStructure(['data', 'links', 'meta'])
            ->assertJsonPath('meta.total', 12)
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('data.0.program_studi.kode', 'TK');
    }

    public function test_daftar_mahasiswa_dengan_filter_dan_pagination(): void
    {
        $target = Mahasiswa::first();

        $this->getJson('/api/mahasiswa?per_halaman=5&urut=ipk&arah=desc')
            ->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.per_page', 5);

        $this->getJson('/api/mahasiswa?cari='.$target->nim)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nim', $target->nim);

        $this->getJson('/api/mahasiswa?angkatan='.$target->angkatan)
            ->assertOk()
            ->assertJsonPath(
                'meta.total',
                Mahasiswa::where('angkatan', $target->angkatan)->count()
            );

        $this->getJson('/api/mahasiswa?program_studi_id='.$this->prodi->id)
            ->assertOk()
            ->assertJsonPath('meta.total', 12);
    }

    public function test_parameter_pagination_dibatasi_maksimal_100(): void
    {
        $this->getJson('/api/mahasiswa?per_halaman=1000')
            ->assertStatus(422)
            ->assertJsonPath('sukses', false)
            ->assertJsonStructure(['sukses', 'pesan', 'galat']);
    }

    public function test_parameter_urut_tidak_boleh_melebihi_daftar_yang_diizinkan(): void
    {
        $this->getJson('/api/mahasiswa?urut=nama;DROP TABLE mahasiswas')
            ->assertStatus(422)
            ->assertJsonPath('sukses', false);

        $this->assertDatabaseCount('mahasiswas', 12);
    }

    public function test_tampilkan_satu_mahasiswa(): void
    {
        $mhs = Mahasiswa::first();

        $this->getJson("/api/mahasiswa/{$mhs->id}")
            ->assertOk()
            ->assertJsonPath('sukses', true)
            ->assertJsonPath('data.id', $mhs->id)
            ->assertJsonPath('data.nim', $mhs->nim)
            ->assertJsonStructure(['data' => ['id', 'nim', 'nama', 'email', 'angkatan', 'ipk', 'aktif', 'program_studi', 'dibuat_pada']]);
    }

    public function test_mahasiswa_tidak_ditemukan(): void
    {
        $this->getJson('/api/mahasiswa/99999')
            ->assertStatus(404)
            ->assertExactJson([
                'sukses' => false,
                'pesan' => 'Sumber daya tidak ditemukan',
            ]);
    }

    public function test_tambah_mahasiswa(): void
    {
        $this->postJson('/api/mahasiswa', [
            'program_studi_id' => $this->prodi->id,
            'nim' => 'H1A125999',
            'nama' => 'Dewi Anggraini',
            'email' => 'dewi.anggraini@example.com',
            'angkatan' => 2025,
            'ipk' => 3.65,
        ])
            ->assertStatus(201)
            ->assertJsonPath('sukses', true)
            ->assertJsonPath('pesan', 'Data mahasiswa berhasil dibuat')
            ->assertJsonPath('data.nim', 'H1A125999')
            ->assertJsonPath('data.program_studi.kode', 'TK');

        $this->assertDatabaseHas('mahasiswas', ['nim' => 'H1A125999']);
    }

    public function test_tambah_mahasiswa_gagal_validasi(): void
    {
        $this->postJson('/api/mahasiswa', [
            'program_studi_id' => $this->prodi->id,
            'nim' => 'H1A125999',
            'nama' => 'Dewi Anggraini',
            'email' => 'dewi.anggraini@example.com',
            'angkatan' => 2025,
            'ipk' => 3.65,
        ])->assertStatus(201);

        $this->postJson('/api/mahasiswa', [
            'program_studi_id' => $this->prodi->id,
            'nim' => 'H1A125999',
            'nama' => 'Dewi Anggraini',
            'email' => 'dewi.anggraini@example.com',
            'angkatan' => 2025,
        ])
            ->assertStatus(422)
            ->assertJsonPath('sukses', false)
            ->assertJsonPath('pesan', 'Data yang dikirim tidak valid')
            ->assertJsonPath('galat.nim.0', 'NIM tersebut sudah terdaftar')
            ->assertJsonPath('galat.email.0', 'Email tersebut sudah terdaftar');
    }

    public function test_validasi_field_wajib_dan_format(): void
    {
        $this->postJson('/api/mahasiswa', [
            'nim' => '',
            'email' => 'bukan-email',
            'angkatan' => 1999,
            'ipk' => 9,
        ])
            ->assertStatus(422)
            ->assertJsonStructure(['sukses', 'pesan', 'galat'])
            ->assertJsonPath('galat.program_studi_id.0', 'Program studi wajib diisi')
            ->assertJsonPath('galat.email.0', 'Format email tidak valid')
            ->assertJsonPath('galat.angkatan.0', 'Tahun angkatan tidak wajar');
    }

    public function test_ubah_mahasiswa(): void
    {
        $mhs = Mahasiswa::first();

        $this->putJson("/api/mahasiswa/{$mhs->id}", ['ipk' => 3.90])
            ->assertOk()
            ->assertJsonPath('sukses', true)
            ->assertJsonPath('pesan', 'Data mahasiswa berhasil diperbarui')
            ->assertJsonPath('data.ipk', 3.9);

        $this->assertDatabaseHas('mahasiswas', ['id' => $mhs->id, 'ipk' => 3.9]);
    }

    public function test_ubah_mahasiswa_hanya_bagian_yang_dikirim(): void
    {
        $mhs = Mahasiswa::first();
        $namaAsli = $mhs->nama;

        $this->patchJson("/api/mahasiswa/{$mhs->id}", ['ipk' => 1.5])
            ->assertOk()
            ->assertJsonPath('data.nama', $namaAsli)
            ->assertJsonPath('data.ipk', 1.5);
    }

    public function test_ubah_mahasiswa_gagal_validasi(): void
    {
        $mhs = Mahasiswa::first();

        $this->putJson("/api/mahasiswa/{$mhs->id}", ['ipk' => 4.5])
            ->assertStatus(422)
            ->assertJsonPath('sukses', false)
            ->assertJsonStructure(['sukses', 'pesan', 'galat']);
    }

    public function test_hapus_mahasiswa(): void
    {
        $mhs = Mahasiswa::first();

        $this->deleteJson("/api/mahasiswa/{$mhs->id}")
            ->assertOk()
            ->assertJsonPath('sukses', true)
            ->assertJsonPath('pesan', 'Data mahasiswa berhasil dihapus');

        $this->assertDatabaseMissing('mahasiswas', ['id' => $mhs->id]);

        $this->getJson("/api/mahasiswa/{$mhs->id}")->assertStatus(404);
    }

    public function test_metode_tidak_diizinkan(): void
    {
        $this->patchJson('/api/mahasiswa')
            ->assertStatus(405)
            ->assertJsonPath('sukses', false)
            ->assertJsonStructure(['sukses', 'pesan', 'diizinkan']);
    }

    public function test_parameter_fields_memilih_kolom(): void
    {
        $respons = $this->getJson('/api/mahasiswa?fields=nim,nama&per_halaman=1')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $baris = $respons->json('data.0');

        $this->assertSame(['nim', 'nama'], array_keys($baris));

        $this->getJson('/api/mahasiswa/1?fields=id,nim,program_studi')
            ->assertOk()
            ->assertJsonPath('sukses', true)
            ->assertJsonMissingPath('data.email')
            ->assertJsonMissingPath('data.ipk');

        $respons = $this->getJson('/api/mahasiswa/1?fields=id,nim,program_studi')->assertOk();

        $this->assertSame(['id', 'nim', 'program_studi'], array_keys($respons->json('data')));
    }

    public function test_parameter_fields_ngawur_diabaikan(): void
    {
        $respons = $this->getJson('/api/mahasiswa?fields=kolom_ngawur&per_halaman=1')->assertOk();

        $this->assertSame(
            ['id', 'nim', 'nama', 'email', 'angkatan', 'ipk', 'aktif', 'program_studi', 'dibuat_pada'],
            array_keys($respons->json('data.0'))
        );
    }
}
