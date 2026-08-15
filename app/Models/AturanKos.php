<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AturanKos extends Model
{
    use HasFactory;

    protected $table = 'aturan_kos';

    protected $fillable = [
        'kos_id',
        'isi_aturan',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function kos()
    {
        return $this->belongsTo(Kos::class, 'kos_id');
    }
}
