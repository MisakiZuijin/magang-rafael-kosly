<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pembayaran extends Model
{
    use HasFactory;

    protected $table = 'pembayaran';

    protected $fillable = [
        'kode_invoice',
        'penghuni_kamar_id',
        'jumlah',
        'porsi_bayar',
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

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->kode_invoice)) {
                $model->kode_invoice = 'INV-' . date('Ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(8));
            }
        });
    }

    public function getRouteKeyName()
    {
        return 'kode_invoice';
    }

    protected function casts(): array
    {
        return [
            'jumlah' => 'integer',
            'porsi_bayar' => 'integer',
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

    public function verifikator()
    {
        return $this->belongsTo(User::class, 'diverifikasi_oleh');
    }

    /**
     * Dapatkan URL bukti transfer efektif (termasuk bukti transfer yang diunggah oleh rekan sekamar jika pembayaran diwakilkan).
     */
    public function getEffectiveBuktiTransferUrlAttribute(): ?string
    {
        if (!empty($this->bukti_transfer_url)) {
            return $this->bukti_transfer_url;
        }

        $pk = $this->penghuniKamar;
        if ($pk && $pk->kamar_id) {
            $kamar = $pk->kamar;
            if ($kamar && $kamar->relationLoaded('penghuniKamar')) {
                $allPks = $kamar->penghuniKamar;
                $allPembayaranLoaded = $allPks->every(fn($p) => $p->relationLoaded('pembayaran'));
                if ($allPembayaranLoaded) {
                    $pMulai1 = $this->periode_mulai instanceof \Carbon\Carbon ? $this->periode_mulai->toDateString() : (string)$this->periode_mulai;
                    $roommatePayment = $allPks->flatMap->pembayaran
                        ->where('id', '!=', $this->id)
                        ->whereNotNull('bukti_transfer_url')
                        ->filter(function($pm) use ($pMulai1) {
                            if ($pMulai1) {
                                $pMulai2 = $pm->periode_mulai instanceof \Carbon\Carbon ? $pm->periode_mulai->toDateString() : (string)$pm->periode_mulai;
                                return $pMulai1 === $pMulai2;
                            }
                            return true;
                        })
                        ->sortByDesc('tanggal_verifikasi')
                        ->first();

                    return $roommatePayment?->bukti_transfer_url;
                }
            }

            $roommatePayment = static::whereHas('penghuniKamar', function ($q) use ($pk) {
                $q->where('kamar_id', $pk->kamar_id);
            })
            ->where('id', '!=', $this->id)
            ->whereNotNull('bukti_transfer_url')
            ->where('periode_mulai', $this->periode_mulai)
            ->latest('tanggal_verifikasi')
            ->first();

            return $roommatePayment?->bukti_transfer_url;
        }

        return null;
    }

    /**
     * Menghasilkan teks badge tarif yang terisolasi dan permanen untuk transaksi ini.
     * Mencegah mutasi label tarif saat ada transaksi baru di kamar lain atau perubahan kapasitas kamar.
     */
    public function getTarifBadgeInfo(?Kamar $passedKamar = null, ?int $passedActiveCount = null): array
    {
        $kamar = $passedKamar ?? ($this->penghuniKamar->kamar ?? null);
        if (!$kamar || $kamar->tipe !== 'berbagi') {
            return [
                'text' => 'Tarif Standar',
                'class' => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
            ];
        }

        // 1. Jika bayar separuh (50%)
        if ((int)$this->porsi_bayar === 50 || str_contains($this->catatan_verifikasi ?? '', 'Tarif 1 Orang') || str_contains($this->catatan_verifikasi ?? '', '50%')) {
            return [
                'text' => 'Tarif 1 Orang',
                'class' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300',
            ];
        }

        // 2. Cek apakah catatan verifikasi memiliki rekaman spesifik tarif
        $catatan = $this->catatan_verifikasi ?? '';
        if (str_contains($catatan, 'Tarif 3 Orang') || str_contains($catatan, '3 Orang') || str_contains($catatan, '3 Penghuni')) {
            return [
                'text' => 'Tarif 3 Orang',
                'class' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300',
            ];
        }
        if (str_contains($catatan, 'Tarif 2 Orang') || str_contains($catatan, '2 Orang')) {
            return [
                'text' => 'Tarif 2 Orang',
                'class' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
            ];
        }

        // 3. Cek jumlah penghuni dari parameter atau relasi kamar jika sudah dimuat
        if ($passedActiveCount !== null && $passedActiveCount >= 3) {
            return [
                'text' => 'Tarif 3 Orang',
                'class' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300',
            ];
        }

        if ($kamar->relationLoaded('penghuniKamar')) {
            $activeCount = $kamar->penghuniKamar->where('status', 'aktif')->count();
            if ($activeCount >= 3) {
                return [
                    'text' => 'Tarif 3 Orang',
                    'class' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300',
                ];
            }
        }

        if (($kamar->kapasitas ?? 2) >= 3) {
            return [
                'text' => 'Tarif 3 Orang',
                'class' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300',
            ];
        }

        return [
            'text' => 'Tarif 2 Orang',
            'class' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
        ];
    }
}
