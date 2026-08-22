<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pencairan extends Model
{
    use HasFactory;

    protected $table = 'pencairan';

    protected $fillable = [
        'kos_id',
        'mitra_id',
        'bulan',
        'tahun',
        'total_pendapatan',
        'status',
        'tanggal_cair',
        'bukti_transfer',
        'catatan',
        'dicairkan_oleh',
    ];

    protected function casts(): array
    {
        return [
            'bulan' => 'integer',
            'tahun' => 'integer',
            'total_pendapatan' => 'integer',
            'tanggal_cair' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function kos()
    {
        return $this->belongsTo(Kos::class, 'kos_id');
    }

    public function mitra()
    {
        return $this->belongsTo(User::class, 'mitra_id');
    }

    public function dicairkanOleh()
    {
        return $this->belongsTo(User::class, 'dicairkan_oleh');
    }
}
