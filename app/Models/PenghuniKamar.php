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

    protected static function booted()
    {
        static::saved(function ($model) {
            // Jika tanggal_keluar diperpanjang menjadi masa depan, reset status notifikasi di kamar
            if ($model->tanggal_keluar && \Carbon\Carbon::parse($model->tanggal_keluar)->isFuture()) {
                if ($model->kamar_id) {
                    Kamar::where('id', $model->kamar_id)->update([
                        'notif_jatuh_tempo_sent_at' => null,
                        'notif_h7_sent_at' => null,
                        'notif_h3_sent_at' => null,
                    ]);
                }
            }
        });
    }

    protected function casts(): array
    {
        return [
            'tanggal_masuk' => 'datetime',
            'tanggal_keluar' => 'datetime',
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
