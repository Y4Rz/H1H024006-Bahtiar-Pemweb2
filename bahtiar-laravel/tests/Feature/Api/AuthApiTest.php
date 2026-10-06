<?php

namespace Tests\Feature\Api;

use App\Models\ProgramStudi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_berhasil_menerbitkan_token(): void
    {
        $respons = $this->postJson('/api/auth/register', [
            'name' => 'Mahasiswa Baru',
            'email' => 'mhs@unsoed.ac.id',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ]);

        $respons->assertStatus(201)
            ->assertJsonPath('sukses', true)
            ->assertJsonPath('pesan', 'Registrasi berhasil')
            ->assertJsonPath('data.pengguna.email', 'mhs@unsoed.ac.id')
            ->assertJsonPath('data.pengguna.peran', 'mahasiswa')
            ->assertJsonStructure(['data' => ['pengguna', 'token']]);

        $this->assertDatabaseHas('users', ['email' => 'mhs@unsoed.ac.id', 'peran' => 'mahasiswa']);
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_login_berhasil_memperbarui_terakhir_login(): void
    {
        $pengguna = User::factory()->create([
            'email' => 'admin@unsoed.ac.id',
            'password' => Hash::make('rahasia123'),
            'peran' => 'admin',
        ]);

        $this->assertNull($pengguna->terakhir_login);

        $respons = $this->postJson('/api/auth/login', [
            'email' => 'admin@unsoed.ac.id',
            'password' => 'rahasia123',
        ]);

        $respons->assertOk()
            ->assertJsonPath('sukses', true)
            ->assertJsonPath('pesan', 'Login berhasil')
            ->assertJsonStructure(['data' => ['pengguna', 'token']]);

        $pengguna->refresh();
        $this->assertNotNull($pengguna->terakhir_login);
    }

    public function test_login_gagal_kredensial_salah(): void
    {
        User::factory()->create([
            'email' => 'admin@unsoed.ac.id',
            'password' => Hash::make('rahasia123'),
            'peran' => 'admin',
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'admin@unsoed.ac.id',
            'password' => 'salah1234',
        ])
            ->assertStatus(401)
            ->assertJsonPath('sukses', false)
            ->assertJsonPath('pesan', 'Email atau kata sandi tidak sesuai');
    }

    public function test_akses_tanpa_token_ditolak_401(): void
    {
        $this->getJson('/api/auth/profil')
            ->assertStatus(401)
            ->assertJsonPath('sukses', false)
            ->assertJsonPath('pesan', 'Token tidak valid atau belum dikirim');

        $this->getJson('/api/mahasiswa')
            ->assertStatus(401)
            ->assertJsonPath('sukses', false);
    }

    public function test_akses_profil_dengan_token_valid(): void
    {
        $pengguna = User::factory()->create(['peran' => 'admin']);
        $token = $pengguna->createToken('token-perangkat', ['mahasiswa:baca', 'mahasiswa:tulis'])->plainTextToken;

        $this->getJson('/api/auth/profil', ['Authorization' => 'Bearer '.$token])
            ->assertOk()
            ->assertJsonPath('sukses', true)
            ->assertJsonPath('data.email', $pengguna->email)
            ->assertJsonPath('data.peran', 'admin');
    }

    public function test_akses_setelah_logout_ditolak(): void
    {
        $pengguna = User::factory()->create(['peran' => 'admin']);
        $token = $pengguna->createToken('token-perangkat', ['mahasiswa:baca', 'mahasiswa:tulis'])->plainTextToken;

        $this->postJson('/api/auth/logout', [], ['Authorization' => 'Bearer '.$token])
            ->assertOk()
            ->assertJsonPath('pesan', 'Logout berhasil');

        // Simulasi permintaan HTTP baru: lupakan guard yang di-cache antar
        // panggilan $this->getJson() dalam satu metode uji.
        $this->app['auth']->forgetGuards();

        // Token sudah dihapus di server sehingga tidak bisa dipakai lagi.
        $this->getJson('/api/auth/profil', ['Authorization' => 'Bearer '.$token])
            ->assertStatus(401);
    }

    public function test_token_tanpa_kemampuan_tulis_ditolak_403(): void
    {
        $prodi = ProgramStudi::create(['kode' => 'TK', 'nama' => 'Teknik Komputer', 'jenjang' => 'S1']);
        $pengguna = User::factory()->create(['peran' => 'mahasiswa']);
        $token = $pengguna->createToken('token-perangkat', ['mahasiswa:baca'])->plainTextToken;

        $this->postJson('/api/mahasiswa', [
            'program_studi_id' => $prodi->id,
            'nim' => 'H1A125001',
            'nama' => 'Coba Tulis',
            'email' => 'coba@example.com',
            'angkatan' => 2025,
            'ipk' => 3.0,
        ], ['Authorization' => 'Bearer '.$token])
            ->assertStatus(403);
    }

    public function test_hapus_mahasiswa_hanya_admin(): void
    {
        $prodi = ProgramStudi::create(['kode' => 'TK', 'nama' => 'Teknik Komputer', 'jenjang' => 'S1']);
        $mhs = \App\Models\Mahasiswa::factory()->create(['program_studi_id' => $prodi->id]);

        // Token admin: boleh hapus.
        $admin = User::factory()->create(['peran' => 'admin']);
        $tokenAdmin = $admin->createToken('t', ['mahasiswa:baca', 'mahasiswa:tulis'])->plainTextToken;

        // Token mahasiswa walau punya tulis tetap ditolak PeranAdmin,
        // di sini mahasiswa hanya punya baca sehingga sudah 403 duluan.
        $mhsUser = User::factory()->create(['peran' => 'mahasiswa']);
        $tokenMhs = $mhsUser->createToken('t', ['mahasiswa:baca', 'mahasiswa:tulis'])->plainTextToken;

        $this->deleteJson("/api/mahasiswa/{$mhs->id}", [], ['Authorization' => 'Bearer '.$tokenMhs])
            ->assertStatus(403)
            ->assertJsonPath('sukses', false);

        // Lupakan guard yang di-cache agar Bearer token berikutnya
        // (milik admin) benar-benar divalidasi ulang.
        $this->app['auth']->forgetGuards();

        $this->deleteJson("/api/mahasiswa/{$mhs->id}", [], ['Authorization' => 'Bearer '.$tokenAdmin])
            ->assertOk()
            ->assertJsonPath('pesan', 'Data mahasiswa berhasil dihapus');
    }

    public function test_ubah_password_memerlukan_password_lama(): void
    {
        $pengguna = User::factory()->create([
            'password' => Hash::make('rahasia123'),
            'peran' => 'mahasiswa',
        ]);
        Sanctum::actingAs($pengguna, ['mahasiswa:baca']);

        // Password lama salah -> 422.
        $this->putJson('/api/auth/password', [
            'password_lama' => 'keliru123',
            'password' => 'baru1234',
            'password_confirmation' => 'baru1234',
        ])->assertStatus(422);

        // Password lama benar -> berhasil, lalu bisa login dengan password baru.
        $this->putJson('/api/auth/password', [
            'password_lama' => 'rahasia123',
            'password' => 'baru1234',
            'password_confirmation' => 'baru1234',
        ])
            ->assertOk()
            ->assertJsonPath('pesan', 'Kata sandi berhasil diubah');

        $this->postJson('/api/auth/login', [
            'email' => $pengguna->email,
            'password' => 'baru1234',
        ])->assertOk();
    }

    public function test_logout_semua_menghapus_seluruh_token(): void
    {
        $pengguna = User::factory()->create(['peran' => 'admin']);
        $tokenA = $pengguna->createToken('a', ['mahasiswa:baca', 'mahasiswa:tulis'])->plainTextToken;
        $pengguna->createToken('b', ['mahasiswa:baca'])->plainTextToken;

        $this->assertDatabaseCount('personal_access_tokens', 2);

        $this->postJson('/api/auth/logout-semua', [], ['Authorization' => 'Bearer '.$tokenA])
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
