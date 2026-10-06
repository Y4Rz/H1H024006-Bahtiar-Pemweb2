<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMahasiswaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $id = $this->route('mahasiswa')->id;

        return [
            'program_studi_id' => ['sometimes', 'integer', 'exists:program_studis,id'],
            'nim' => ['sometimes', 'string', 'max:20', 'unique:mahasiswas,nim,'.$id],
            'nama' => ['sometimes', 'string', 'max:100'],
            'email' => ['sometimes', 'email', 'max:100', 'unique:mahasiswas,email,'.$id],
            'angkatan' => ['sometimes', 'integer', 'min:2000', 'max:2100'],
            'ipk' => ['sometimes', 'numeric', 'min:0', 'max:4'],
            'aktif' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Get the custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'program_studi_id.exists' => 'Program studi yang dipilih tidak ditemukan',
            'nim.max' => 'NIM maksimal 20 karakter',
            'nim.unique' => 'NIM tersebut sudah terdaftar',
            'nama.max' => 'Nama maksimal 100 karakter',
            'email.email' => 'Format email tidak valid',
            'email.unique' => 'Email tersebut sudah terdaftar',
            'angkatan.min' => 'Tahun angkatan tidak wajar',
            'angkatan.max' => 'Tahun angkatan tidak wajar',
            'ipk.numeric' => 'IPK harus berupa angka',
            'ipk.max' => 'IPK tidak boleh lebih dari 4',
        ];
    }
}
