<?php

namespace Tests\Feature\Api;

use App\Models\Mahasiswa;
use App\Models\ProgramStudi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgramStudiApiTest extends TestCase
{
    use RefreshDatabase;

    private ProgramStudi $tk;

    private ProgramStudi $if;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tk = ProgramStudi::create(['kode' => 'TK', 'nama' => 'Teknik Komputer', 'jenjang' => 'S1']);
        $this->if = ProgramStudi::create(['kode' => 'IF', 'nama' => 'Informatika', 'jenjang' => 'S1']);

        Mahasiswa::factory()->count(5)->create(['program_studi_id' => $this->tk->id]);
        Mahasiswa::factory()->count(2)->create(['program_studi_id' => $this->if->id]);
    }

    public function test_daftar_program_studi(): void
    {
        $respons = $this->getJson('/api/program-studi')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure(['data', 'links', 'meta']);

        $this->assertSame(
            ['id', 'kode', 'nama', 'jenjang', 'jumlah_mahasiswa', 'dibuat_pada'],
            array_keys($respons->json('data.0'))
        );
    }

    public function test_tampilkan_satu_program_studi(): void
    {
        $this->getJson("/api/program-studi/{$this->tk->id}")
            ->assertOk()
            ->assertJsonPath('sukses', true)
            ->assertJsonPath('data.kode', 'TK')
            ->assertJsonPath('data.jumlah_mahasiswa', 5);
    }

    public function test_program_studi_tidak_ditemukan(): void
    {
        $this->getJson('/api/program-studi/99999')
            ->assertStatus(404)
            ->assertExactJson(['sukses' => false, 'pesan' => 'Sumber daya tidak ditemukan']);
    }

    public function test_daftar_mahasiswa_per_program_studi(): void
    {
        $this->getJson("/api/program-studi/{$this->tk->id}/mahasiswa")
            ->assertOk()
            ->assertJsonPath('sukses', true)
            ->assertJsonPath('program_studi.kode', 'TK')
            ->assertJsonPath('program_studi.jumlah_mahasiswa', 5)
            ->assertJsonPath('meta.total', 5)
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('data.0.program_studi.kode', 'TK')
            ->assertJsonStructure(['data', 'links', 'meta']);

        $this->getJson("/api/program-studi/{$this->if->id}/mahasiswa")
            ->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    public function test_daftar_mahasiswa_per_program_studi_terpaginasi(): void
    {
        $this->getJson("/api/program-studi/{$this->tk->id}/mahasiswa?per_halaman=2")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 5)
            ->assertJsonPath('meta.last_page', 3)
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_daftar_mahasiswa_per_program_studi_dengan_filter_dan_urut(): void
    {
        $target = Mahasiswa::where('program_studi_id', $this->tk->id)->first();

        $this->getJson("/api/program-studi/{$this->tk->id}/mahasiswa?cari={$target->nama}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nim', $target->nim);

        $this->getJson("/api/program-studi/{$this->tk->id}/mahasiswa?urut=ipk&arah=desc&per_halaman=1")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath(
                'data.0.ipk',
                (float) Mahasiswa::where('program_studi_id', $this->tk->id)->max('ipk')
            );
    }

    public function test_daftar_mahasiswa_per_program_studi_dengan_parameter_fields(): void
    {
        $respons = $this->getJson("/api/program-studi/{$this->tk->id}/mahasiswa?fields=nim,nama&per_halaman=1")
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->assertSame(['nim', 'nama'], array_keys($respons->json('data.0')));
    }

    public function test_daftar_mahasiswa_program_studi_tidak_ditemukan(): void
    {
        $this->getJson('/api/program-studi/99999/mahasiswa')
            ->assertStatus(404)
            ->assertExactJson(['sukses' => false, 'pesan' => 'Sumber daya tidak ditemukan']);
    }

    public function test_parameter_tidak_valid_ditolak(): void
    {
        $this->getJson("/api/program-studi/{$this->tk->id}/mahasiswa?per_halaman=0")
            ->assertStatus(422)
            ->assertJsonPath('sukses', false);

        $this->getJson("/api/program-studi/{$this->tk->id}/mahasiswa?urut=email")
            ->assertStatus(422)
            ->assertJsonPath('sukses', false);
    }
}
