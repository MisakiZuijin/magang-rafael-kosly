<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kos extends Model
{
    use HasFactory;

    protected $table = 'kos';

    protected $fillable = [
        'mitra_id',
        'nama',
        'foto',
        'alamat',
        'latitude',
        'longitude',
        'deskripsi',
        'no_rekening',
        'bank',
        'nama_pemilik_rekening',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'created_at' => 'datetime',
        ];
    }

    public function mitra()
    {
        return $this->belongsTo(User::class, 'mitra_id');
    }

    public function kamar()
    {
        return $this->hasMany(Kamar::class, 'kos_id');
    }

    public function aturanKos()
    {
        return $this->hasMany(AturanKos::class, 'kos_id');
    }
}
