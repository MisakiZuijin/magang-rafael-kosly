<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'nama',
        'email',
        'password',
        'no_hp',
        'role',
        'foto_profile',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function kos()
    {
        return $this->hasMany(Kos::class, 'mitra_id');
    }

    public function penghuniKamar()
    {
        return $this->hasMany(PenghuniKamar::class, 'penghuni_id');
    }

    public function notifikasi()
    {
        return $this->hasMany(Notifikasi::class, 'user_id');
    }

    public function logAktivitas()
    {
        return $this->hasMany(LogAktivitas::class, 'user_id');
    }

    public function verifikasiPembayaran()
    {
        return $this->hasMany(Pembayaran::class, 'diverifikasi_oleh');
    }

    public function pengumuman()
    {
        return $this->hasMany(Pengumuman::class, 'dibuat_oleh');
    }

    public function logPopupAturan()
    {
        return $this->hasMany(LogPopupAturan::class, 'penghuni_id');
    }
}
