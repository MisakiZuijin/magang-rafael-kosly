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

    /**
     * Dapatkan URL bukti transfer efektif (termasuk bukti transfer yang diunggah oleh rekan sekamar jika pembayaran diwakilkan).
     */
    public function getEffectiveBuktiTransferUrlAttribute(): ?string
    {
        if (!empty($this->bukti_transfer_url)) {
            return $this->bukti_transfer_url;
        }

        if ($this->penghuniKamar && $this->penghuniKamar->kamar_id) {
            $roommatePayment = static::whereHas('penghuniKamar', function ($q) {
                $q->where('kamar_id', $this->penghuniKamar->kamar_id);
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
    public function getTarifBadgeInfo(): array
    {
        $kamar = $this->penghuniKamar->kamar ?? null;
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

        // 3. Hitung jumlah penghuni aktif di kamar ini atau transaksi pembayaran bersamaan
        $kamarId = $this->penghuniKamar->kamar_id ?? null;
        if ($kamarId) {
            $activeCount = \App\Models\PenghuniKamar::where('kamar_id', $kamarId)
                ->where('status', 'aktif')
                ->count();

            if ($activeCount >= 3) {
                return [
                    'text' => 'Tarif 3 Orang',
                    'class' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300',
                ];
            }

            // Jika status terverifikasi, cek juga apakah pada transaksi historis ada 3 penghuni
            if ($this->status === 'terverifikasi') {
                $roommatePaymentsCount = static::whereHas('penghuniKamar', function($q) use ($kamarId) {
                    $q->where('kamar_id', $kamarId);
                })
                ->where('porsi_bayar', 100)
                ->where(function($q) {
                    if ($this->periode_mulai && $this->periode_selesai) {
                        $q->where('periode_mulai', $this->periode_mulai)
                          ->where('periode_selesai', $this->periode_selesai);
                    } elseif ($this->tanggal_bayar) {
                        $q->whereDate('tanggal_bayar', $this->tanggal_bayar);
                    } elseif ($this->created_at) {
                        $q->whereDate('created_at', $this->created_at);
                    }
                })
                ->count();

                if ($roommatePaymentsCount >= 3) {
                    return [
                        'text' => 'Tarif 3 Orang',
                        'class' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300',
                    ];
                }
            }
        }

        return [
            'text' => 'Tarif 2 Orang',
            'class' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
        ];
    }
}
