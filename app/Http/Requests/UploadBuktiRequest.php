<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadBuktiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pembayaran_id' => 'required|exists:pembayaran,id',
            'bukti_transfer' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ];
    }
}
