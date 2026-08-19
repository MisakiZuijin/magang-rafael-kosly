<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pembayaran extends Model
{
    use HasFactory;

    protected $table = 'pembayaran';

    protected $fillable = [
        'penghuni_kamar_id',
        'jumlah',
        'tipe_perpanjangan',
        'jumlah_hari',
        'periode_mulai',
        'periode_selesai',
        'status',
        'bukti_transfer_url',
        'tanggal_bayar',
        'diverifikasi_oleh',
        'tanggal_verifikasi',
        'catatan_verifikasi',
    ];

    protected function casts(): array
    {
        return [
            'jumlah' => 'decimal:2',
            'jumlah_hari' => 'integer',
            'periode_mulai' => 'date',
            'periode_selesai' => 'date',
            'tanggal_bayar' => 'date',
            'tanggal_verifikasi' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function penghuniKamar()
    {
        return $this->belongsTo(PenghuniKamar::class, 'penghuni_kamar_id');
    }

    public function diverifikasiOleh()
    {
        return $this->belongsTo(User::class, 'diverifikasi_oleh');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'diverifikasi_oleh');
    }
}
