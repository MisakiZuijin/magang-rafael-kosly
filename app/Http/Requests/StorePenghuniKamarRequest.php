<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePenghuniKamarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'kamar_id' => 'required|exists:kamar,id',
            'penghuni_id' => 'required|exists:users,id',
            'tanggal_masuk' => 'required|date',
            'tanggal_keluar' => 'nullable|date|after:tanggal_masuk',
            'durasi' => 'required|in:harian,bulanan',
        ];
    }
}
