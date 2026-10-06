<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMatakuliahRequest extends FormRequest
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
        $id = $this->route('matakuliah')->id;

        return [
            'kode' => ['sometimes', 'string', 'max:10', 'unique:matakuliahs,kode,'.$id],
            'nama' => ['sometimes', 'string', 'max:100'],
            'sks' => ['sometimes', 'integer', 'min:1', 'max:6'],
            'semester' => ['sometimes', 'integer', 'min:1', 'max:8'],
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
            'kode.max' => 'Kode mata kuliah maksimal 10 karakter',
            'kode.unique' => 'Kode mata kuliah tersebut sudah terdaftar',
            'nama.max' => 'Nama mata kuliah maksimal 100 karakter',
            'sks.min' => 'SKS minimal 1',
            'sks.max' => 'SKS maksimal 6',
            'semester.min' => 'Semester minimal 1',
            'semester.max' => 'Semester maksimal 8',
        ];
    }
}
