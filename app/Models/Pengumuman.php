<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pengumuman extends Model
{
    use HasFactory;

    protected $table = 'pengumuman';

    protected $fillable = [
        'judul',
        'isi',
        'tipe',
        'channel',
        'dibuat_oleh',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function dibuatOleh()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function pembuat()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function targets()
    {
        return $this->hasMany(PengumumanTarget::class, 'pengumuman_id');
    }

    public function target()
    {
        return $this->hasMany(PengumumanTarget::class, 'pengumuman_id');
    }

    /**
     * Menghasilkan teks deskripsi target pengumuman secara in-memory tanpa query N+1.
     */
    public function getTargetDescription(bool $isMitra = false, ?array $kosMap = null): string
    {
        if (!$this->relationLoaded('targets') || $this->targets->isEmpty()) {
            return $isMitra ? 'Semua Kos Milik Anda' : 'Semua User / Anak Kos';
        }

        $firstTarget = $this->targets->first();
        $targetTipe = $firstTarget->target_tipe;

        if ($targetTipe === 'kos') {
            $kosNames = $this->targets->map(function ($t) use ($kosMap) {
                if ($kosMap && isset($kosMap[$t->target_id])) {
                    return $kosMap[$t->target_id];
                }
                return $t->kos ? $t->kos->nama : null;
            })->filter()->unique()->values()->toArray();

            return !empty($kosNames) ? 'Target Kos: ' . implode(', ', $kosNames) : ($isMitra ? 'Semua Kos Milik Anda' : 'Semua User / Anak Kos');
        } elseif ($targetTipe === 'kamar') {
            $kamarNames = $this->targets->map(function ($t) use ($kosMap) {
                if ($t->kamar) {
                    $kosNama = ($kosMap && isset($kosMap[$t->kamar->kos_id]))
                        ? $kosMap[$t->kamar->kos_id]
                        : ($t->kamar->kos ? $t->kamar->kos->nama : '-');
                    return 'Kamar ' . $t->kamar->kode_kamar . ' (' . $kosNama . ')';
                }
                return null;
            })->filter()->unique()->values()->toArray();

            return !empty($kamarNames) ? 'Target Kamar: ' . implode(', ', $kamarNames) : 'Target Kamar';
        }

        return $isMitra ? 'Semua Kos Milik Anda' : 'Semua User / Anak Kos';
    }
}
