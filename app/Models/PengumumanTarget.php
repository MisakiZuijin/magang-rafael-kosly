<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengumumanTarget extends Model
{
    use HasFactory;

    protected $table = 'pengumuman_target';

    protected $fillable = [
        'pengumuman_id',
        'target_tipe',
        'target_id',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function pengumuman()
    {
        return $this->belongsTo(Pengumuman::class, 'pengumuman_id');
    }

    public function kos()
    {
        return $this->belongsTo(Kos::class, 'target_id');
    }

    public function kamar()
    {
        return $this->belongsTo(Kamar::class, 'target_id');
    }
}
