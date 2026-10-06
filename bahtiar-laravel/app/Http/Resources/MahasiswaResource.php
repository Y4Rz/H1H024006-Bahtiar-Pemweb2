<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MahasiswaResource extends JsonResource
{
    /**
     * Daftar kolom yang boleh dipilih klien melalui parameter ?fields=.
     *
     * @var list<string>
     */
    public const KOLOM_TERSEDIA = [
        'id',
        'nim',
        'nama',
        'email',
        'angkatan',
        'ipk',
        'aktif',
        'program_studi',
        'dibuat_pada',
    ];

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'nim' => $this->nim,
            'nama' => $this->nama,
            'email' => $this->email,
            'angkatan' => $this->angkatan,
            'ipk' => (float) $this->ipk,
            'aktif' => $this->aktif,
            'program_studi' => $this->whenLoaded('programStudi', fn () => [
                'id' => $this->programStudi->id,
                'kode' => $this->programStudi->kode,
                'nama' => $this->programStudi->nama,
            ]),
            'dibuat_pada' => $this->created_at->toIso8601String(),
        ];

        $pilihan = static::kolomDipilih($request);

        return $pilihan === null
            ? $data
            : array_intersect_key($data, array_flip($pilihan));
    }

    /**
     * Terjemahkan nilai parameter ?fields= menjadi daftar kolom yang diizinkan.
     *
     * Mengembalikan null bila klien tidak meminta kolom tertentu atau bila
     * seluruh kolom yang diminta tidak dikenal, sehingga bentuk respons tetap
     * lengkap dan tidak pernah menjadi objek kosong.
     *
     * @return list<string>|null
     */
    public static function kolomDipilih(Request $request): ?array
    {
        $diminta = $request->query('fields');

        if (! is_string($diminta) || trim($diminta) === '') {
            return null;
        }

        $daftar = array_values(array_filter(
            array_map('trim', explode(',', $diminta)),
            fn (string $kolom): bool => $kolom !== '',
        ));

        $sah = array_values(array_intersect($daftar, self::KOLOM_TERSEDIA));

        return $sah === [] ? null : $sah;
    }
}
