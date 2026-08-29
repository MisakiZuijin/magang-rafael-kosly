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
        'slug',
        'foto',
        'alamat',
        'link_gmaps',
        'deskripsi',
        'no_rekening',
        'bank',
        'nama_pemilik_rekening',
        'is_locked',
    ];

    protected static function booted()
    {
        static::saving(function ($kos) {
            if (empty($kos->slug) || $kos->isDirty('nama')) {
                $slug = \Illuminate\Support\Str::slug($kos->nama);
                $originalSlug = $slug;
                $count = 1;
                while (static::where('slug', $slug)->where('id', '!=', $kos->id ?? 0)->exists()) {
                    $slug = $originalSlug . '-' . $count++;
                }
                $kos->slug = $slug;
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected function casts(): array
    {
        return [
            'is_locked' => 'boolean',
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
