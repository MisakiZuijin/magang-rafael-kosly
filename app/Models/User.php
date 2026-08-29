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
        'slug',
        'email',
        'password',
        'no_hp',
        'role',
        'foto_profile',
        'is_active',
    ];

    protected static function booted()
    {
        static::saving(function ($user) {
            if (empty($user->slug) || $user->isDirty('nama')) {
                $slug = \Illuminate\Support\Str::slug($user->nama);
                $originalSlug = $slug;
                $count = 1;
                while (static::where('slug', $slug)->where('id', '!=', $user->id ?? 0)->exists()) {
                    $slug = $originalSlug . '-' . $count++;
                }
                $user->slug = $slug;
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

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

    public function hasRole(string|array $roles): bool
    {
        if (is_array($roles)) {
            return $this->hasAnyRole($roles);
        }
        $currentRole = $this->role;
        if ($roles === 'superadmin') {
            return in_array($currentRole, ['super_admin', 'superadmin']);
        }
        return $currentRole === $roles;
    }

    public function hasAnyRole(array $roles): bool
    {
        $currentRole = $this->role;
        $normalizedRoles = array_map(function($r) {
            return $r === 'superadmin' ? 'super_admin' : $r;
        }, $roles);
        if (in_array('superadmin', $roles)) {
            $normalizedRoles[] = 'super_admin';
        }
        return in_array($currentRole, $normalizedRoles);
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
}
