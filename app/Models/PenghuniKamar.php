<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PenghuniKamar extends Model
{
    use HasFactory;

    protected $table = 'penghuni_kamar';

    protected $fillable = [
        'kamar_id',
        'penghuni_id',
        'tanggal_masuk',
        'tanggal_keluar',
        'durasi',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_masuk' => 'date',
            'tanggal_keluar' => 'date',
            'created_at' => 'datetime',
        ];
    }

    public function kamar()
    {
        return $this->belongsTo(Kamar::class, 'kamar_id');
    }

    public function penghuni()
    {
        return $this->belongsTo(User::class, 'penghuni_id');
    }

    public function pembayaran()
    {
        return $this->hasMany(Pembayaran::class, 'penghuni_kamar_id');
    }
}
