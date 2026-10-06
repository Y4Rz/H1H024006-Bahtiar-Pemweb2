<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MahasiswaResource;
use App\Http\Resources\ProgramStudiResource;
use App\Models\Mahasiswa;
use App\Models\ProgramStudi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class ProgramStudiController extends Controller
{
    /**
     * Kolom yang diizinkan sebagai sumber parameter ?urut= pada daftar mahasiswa.
     *
     * @var list<string>
     */
    private const KOLOM_URUT = ['nama', 'nim', 'angkatan', 'ipk'];

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $parameter = $request->validate([
            'cari' => ['nullable', 'string', 'max:100'],
            'urut' => ['nullable', Rule::in(['kode', 'nama', 'jenjang'])],
            'arah' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_halaman' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $kueri = ProgramStudi::query()->withCount('mahasiswa');

        if (filled($parameter['cari'] ?? null)) {
            $kataKunci = $parameter['cari'];

            $kueri->where(function ($sub) use ($kataKunci) {
                $sub->where('nama', 'like', '%'.$kataKunci.'%')
                    ->orWhere('kode', 'like', '%'.$kataKunci.'%');
            });
        }

        $kueri->orderBy(
            $parameter['urut'] ?? 'kode',
            ($parameter['arah'] ?? 'asc') === 'desc' ? 'desc' : 'asc'
        );

        return ProgramStudiResource::collection(
            $kueri->paginate($parameter['per_halaman'] ?? 10)
        );
    }

    /**
     * Display the specified resource.
     *
     * Nama parameter $program_studi wajib sama dengan nama parameter rute
     * {program_studi} agar route model binding bekerja.
     */
    public function show(ProgramStudi $program_studi): JsonResponse
    {
        $program_studi->loadCount('mahasiswa');

        return response()->json([
            'sukses' => true,
            'data' => new ProgramStudiResource($program_studi),
        ]);
    }

    /**
     * Daftar mahasiswa pada satu program studi.
     *
     * Dipasangkan dengan rute api/program-studi/{program_studi}/mahasiswa.
     * Nilai {program_studi} yang dikirim klien adalah id program studi.
     */
    public function mahasiswa(Request $request, ProgramStudi $program_studi): AnonymousResourceCollection
    {
        $parameter = $request->validate([
            'cari' => ['nullable', 'string', 'max:100'],
            'angkatan' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'urut' => ['nullable', Rule::in(self::KOLOM_URUT)],
            'arah' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_halaman' => ['nullable', 'integer', 'min:1', 'max:100'],
            'fields' => ['nullable', 'string', 'max:200'],
        ]);

        $program_studi->loadCount('mahasiswa');

        $kueri = Mahasiswa::query()
            ->where('program_studi_id', $program_studi->id)
            ->with('programStudi');

        if (filled($parameter['cari'] ?? null)) {
            $kataKunci = $parameter['cari'];

            $kueri->where(function ($sub) use ($kataKunci) {
                $sub->where('nama', 'like', '%'.$kataKunci.'%')
                    ->orWhere('nim', 'like', '%'.$kataKunci.'%');
            });
        }

        if (isset($parameter['angkatan'])) {
            $kueri->where('angkatan', $parameter['angkatan']);
        }

        $kueri->orderBy(
            $parameter['urut'] ?? 'nama',
            ($parameter['arah'] ?? 'asc') === 'desc' ? 'desc' : 'asc'
        );

        return MahasiswaResource::collection(
            $kueri->paginate($parameter['per_halaman'] ?? 10)
        )->additional([
            'sukses' => true,
            'pesan' => 'Daftar mahasiswa program studi '.$program_studi->nama,
            'program_studi' => new ProgramStudiResource($program_studi),
        ]);
    }
}
