<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreKosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mitra_id' => 'required|exists:users,id',
            'nama' => 'required|string|max:100',
            'alamat' => 'nullable|string',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'deskripsi' => 'nullable|string',
            'no_rekening' => 'nullable|string|max:50',
            'bank' => 'nullable|string|max:50',
            'nama_pemilik_rekening' => 'nullable|string|max:100',
        ];
    }
}
