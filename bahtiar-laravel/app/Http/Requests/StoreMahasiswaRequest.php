<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreMahasiswaRequest extends FormRequest
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
        return [
            'program_studi_id' => ['required', 'integer', 'exists:program_studis,id'],
            'nim' => ['required', 'string', 'max:20', 'unique:mahasiswas,nim'],
            'nama' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:100', 'unique:mahasiswas,email'],
            'angkatan' => ['required', 'integer', 'min:2000', 'max:2100'],
            'ipk' => ['nullable', 'numeric', 'min:0', 'max:4'],
            'aktif' => ['nullable', 'boolean'],
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
            'program_studi_id.required' => 'Program studi wajib diisi',
            'program_studi_id.exists' => 'Program studi yang dipilih tidak ditemukan',
            'nim.required' => 'NIM wajib diisi',
            'nim.max' => 'NIM maksimal 20 karakter',
            'nim.unique' => 'NIM tersebut sudah terdaftar',
            'nama.required' => 'Nama wajib diisi',
            'nama.max' => 'Nama maksimal 100 karakter',
            'email.required' => 'Email wajib diisi',
            'email.email' => 'Format email tidak valid',
            'email.unique' => 'Email tersebut sudah terdaftar',
            'angkatan.required' => 'Angkatan wajib diisi',
            'angkatan.min' => 'Tahun angkatan tidak wajar',
            'angkatan.max' => 'Tahun angkatan tidak wajar',
            'ipk.numeric' => 'IPK harus berupa angka',
            'ipk.max' => 'IPK tidak boleh lebih dari 4',
        ];
    }
}
