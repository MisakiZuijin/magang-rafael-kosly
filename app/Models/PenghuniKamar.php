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
        'notif_jatuh_tempo_sent_at',
    ];

    protected static function booted()
    {
        static::updating(function ($model) {
            // Jika tanggal_keluar diubah menjadi tanggal masa depan (perpanjangan sewa), reset notifikasi jatuh tempo
            if ($model->isDirty('tanggal_keluar') && $model->tanggal_keluar && \Carbon\Carbon::parse($model->tanggal_keluar)->isFuture()) {
                $model->notif_jatuh_tempo_sent_at = null;
            }
        });
    }

    protected function casts(): array
    {
        return [
            'tanggal_masuk' => 'datetime',
            'tanggal_keluar' => 'datetime',
            'notif_jatuh_tempo_sent_at' => 'datetime',
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
