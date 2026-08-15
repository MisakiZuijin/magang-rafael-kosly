<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePengumumanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'judul' => 'required|string|max:200',
            'isi' => 'required|string',
            'tipe' => 'required|in:pembayaran,aturan,info',
            'target_tipe' => 'required|in:kos,kamar,semua',
            'target_ids' => 'nullable|array',
            'target_ids.*' => 'integer',
        ];
    }
}
