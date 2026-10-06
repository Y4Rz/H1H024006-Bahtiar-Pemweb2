<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMahasiswaRequest;
use App\Http\Requests\UpdateMahasiswaRequest;
use App\Http\Resources\MahasiswaResource;
use App\Models\Mahasiswa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class MahasiswaController extends Controller
{
    /**
     * Kolom yang diizinkan sebagai sumber parameter ?urut=.
     * Nilai di luar daftar ini diabaikan agar tidak dapat disisipkan
     * ke klausa ORDER BY.
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
            'angkatan' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'program_studi_id' => ['nullable', 'integer', 'exists:program_studis,id'],
            'urut' => ['nullable', Rule::in(self::KOLOM_URUT)],
            'arah' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_halaman' => ['nullable', 'integer', 'min:1', 'max:100'],
            'fields' => ['nullable', 'string', 'max:200'],
        ]);

        $kueri = Mahasiswa::query()->with('programStudi');

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

        if (isset($parameter['program_studi_id'])) {
            $kueri->where('program_studi_id', $parameter['program_studi_id']);
        }

        $kueri->orderBy(
            $parameter['urut'] ?? 'nama',
            ($parameter['arah'] ?? 'asc') === 'desc' ? 'desc' : 'asc'
        );

        $perHalaman = $parameter['per_halaman'] ?? 10;

        return MahasiswaResource::collection($kueri->paginate($perHalaman));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMahasiswaRequest $request): JsonResponse
    {
        $mahasiswa = Mahasiswa::create($request->validated());

        $mahasiswa->refresh();
        $mahasiswa->load('programStudi');

        return response()->json([
            'sukses' => true,
            'pesan' => 'Data mahasiswa berhasil dibuat',
            'data' => new MahasiswaResource($mahasiswa),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Mahasiswa $mahasiswa): JsonResponse
    {
        $mahasiswa->load('programStudi');

        return response()->json([
            'sukses' => true,
            'data' => new MahasiswaResource($mahasiswa),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMahasiswaRequest $request, Mahasiswa $mahasiswa): JsonResponse
    {
        $mahasiswa->update($request->validated());

        $mahasiswa->load('programStudi');

        return response()->json([
            'sukses' => true,
            'pesan' => 'Data mahasiswa berhasil diperbarui',
            'data' => new MahasiswaResource($mahasiswa),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Mahasiswa $mahasiswa): JsonResponse
    {
        $mahasiswa->delete();

        return response()->json([
            'sukses' => true,
            'pesan' => 'Data mahasiswa berhasil dihapus',
        ]);
    }
}
