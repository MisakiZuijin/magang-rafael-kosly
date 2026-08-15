<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreKamarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'kos_id' => 'required|exists:kos,id',
            'kode_kamar' => 'required|string|max:20',
            'tipe' => 'required|in:standar,berbagi',
            'harga_per_hari' => 'nullable|numeric|min:0',
            'harga_per_bulan' => 'required|numeric|min:0',
            'kapasitas' => 'required|integer|min:1',
        ];
    }
}
