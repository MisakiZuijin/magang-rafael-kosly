<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kamar extends Model
{
    use HasFactory;

    protected $table = 'kamar';

    protected $fillable = [
        'kos_id',
        'kode_kamar',
        'tipe',
        'detail',
        'foto',
        'harga_per_hari',
        'harga_per_minggu',
        'harga_per_bulan',
        'kapasitas',
        'status',
        'wa_group_id',
        'link_grup_wa',
    ];

    protected function casts(): array
    {
        return [
            'foto' => 'array',
            'harga_per_hari' => 'integer',
            'harga_per_minggu' => 'integer',
            'harga_per_bulan' => 'integer',
            'kapasitas' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function kos()
    {
        return $this->belongsTo(Kos::class, 'kos_id');
    }

    public function penghuniKamar()
    {
        return $this->hasMany(PenghuniKamar::class, 'kamar_id');
    }
}
