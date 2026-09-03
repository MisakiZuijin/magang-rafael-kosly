<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasGmapsQuery;

class Kantor extends Model
{
    use HasFactory, HasGmapsQuery;

    protected $table = 'kantor';

    protected $appends = [
        'gmaps_query',
    ];

    protected $fillable = [
        'nama',
        'slug',
        'alamat',
        'link_gmaps',
        'no_telp',
        'is_active',
    ];

    protected static function booted()
    {
        static::saving(function ($kantor) {
            if (empty($kantor->slug) || $kantor->isDirty('nama')) {
                $slug = \Illuminate\Support\Str::slug($kantor->nama);
                $originalSlug = $slug;
                $count = 1;
                while (static::where('slug', $slug)->where('id', '!=', $kantor->id ?? 0)->exists()) {
                    $slug = $originalSlug . '-' . $count++;
                }
                $kantor->slug = $slug;
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
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
