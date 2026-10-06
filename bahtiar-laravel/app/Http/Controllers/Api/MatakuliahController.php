<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMatakuliahRequest;
use App\Http\Requests\UpdateMatakuliahRequest;
use App\Http\Resources\MatakuliahResource;
use App\Models\Matakuliah;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class MatakuliahController extends Controller
{
    /**
     * Kolom yang diizinkan sebagai sumber parameter ?urut=.
     *
     * @var list<string>
     */
    private const KOLOM_URUT = ['kode', 'nama', 'sks', 'semester'];

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $parameter = $request->validate([
            'cari' => ['nullable', 'string', 'max:100'],
            'sks' => ['nullable', 'integer', 'min:1', 'max:6'],
            'semester' => ['nullable', 'integer', 'min:1', 'max:8'],
            'urut' => ['nullable', Rule::in(self::KOLOM_URUT)],
            'arah' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_halaman' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $kueri = Matakuliah::query()->withCount('mahasiswa');

        if (filled($parameter['cari'] ?? null)) {
            $kataKunci = $parameter['cari'];

            $kueri->where(function ($sub) use ($kataKunci) {
                $sub->where('nama', 'like', '%'.$kataKunci.'%')
                    ->orWhere('kode', 'like', '%'.$kataKunci.'%');
            });
        }

        if (isset($parameter['sks'])) {
            $kueri->where('sks', $parameter['sks']);
        }

        if (isset($parameter['semester'])) {
            $kueri->where('semester', $parameter['semester']);
        }

        $kueri->orderBy(
            $parameter['urut'] ?? 'kode',
            ($parameter['arah'] ?? 'asc') === 'desc' ? 'desc' : 'asc'
        );

        $perHalaman = $parameter['per_halaman'] ?? 10;

        return MatakuliahResource::collection($kueri->paginate($perHalaman));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMatakuliahRequest $request): JsonResponse
    {
        $matakuliah = Matakuliah::create($request->validated());

        $matakuliah->loadCount('mahasiswa');

        return response()->json([
            'sukses' => true,
            'pesan' => 'Data mata kuliah berhasil dibuat',
            'data' => new MatakuliahResource($matakuliah),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Matakuliah $matakuliah): JsonResponse
    {
        $matakuliah->loadCount('mahasiswa');

        return response()->json([
            'sukses' => true,
            'data' => new MatakuliahResource($matakuliah),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMatakuliahRequest $request, Matakuliah $matakuliah): JsonResponse
    {
        $matakuliah->update($request->validated());

        $matakuliah->loadCount('mahasiswa');

        return response()->json([
            'sukses' => true,
            'pesan' => 'Data mata kuliah berhasil diperbarui',
            'data' => new MatakuliahResource($matakuliah),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Matakuliah $matakuliah): JsonResponse
    {
        $matakuliah->delete();

        return response()->json([
            'sukses' => true,
            'pesan' => 'Data mata kuliah berhasil dihapus',
        ]);
    }
}
