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
            // Reset status notifikasi kamar agar jika masa sewa diubah atau diperpanjang, siklus notifikasi dapat berjalan
            if ($model->kamar_id) {
                Kamar::where('id', $model->kamar_id)->update([
                    'notif_jatuh_tempo_sent_at' => null,
                    'notif_h7_sent_at' => null,
                    'notif_h3_sent_at' => null,
                ]);
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

    /**
     * Menentukan status pembayaran penghuni:
     * 1. 'belum_bayar_awal' (Belum Bayar Biaya Awal)
     * 2. 'belum_bayar_perpanjangan' (Belum Bayar Perpanjangan)
     * 3. 'sudah_membayar' (Sudah Membayar)
     */
    public function getStatusPembayaranInfo(): array
    {
        // Pastikan relasi pembayaran telah dimuat
        $pembayarans = $this->relationLoaded('pembayaran') ? $this->pembayaran : $this->pembayaran()->get();

        // 1. Cek apakah ada pembayaran awal yang telah diverifikasi
        $hasVerifiedPayment = $pembayarans->where('status', 'terverifikasi')->isNotEmpty();

        // Cek kondisi kamar berdua / berbagi (skema 50% 50%)
        $kamar = $this->relationLoaded('kamar') ? $this->kamar : $this->kamar()->first();
        $isBerbagi2Orang = false;
        $roommateUnpaidAwal = false;
        $roommateName = '';

        if ($kamar && ($kamar->tipe === 'berbagi' || $kamar->kapasitas >= 2)) {
            $roommates = static::with(['penghuni', 'pembayaran'])
                ->where('kamar_id', $this->kamar_id)
                ->where('status', 'aktif')
                ->where('id', '!=', $this->id)
                ->get();

            // Jika ada teman sekamar di kamar kapasitas 2 orang
            if ($roommates->count() === 1) {
                $isBerbagi2Orang = true;
                $roommate = $roommates->first();

                // Cek apakah kamar sudah lunas 100% oleh salah satu pihak
                $isRoomFullPaid = $pembayarans->where('status', 'terverifikasi')->where('porsi_bayar', 100)->isNotEmpty()
                    || $roommate->pembayaran->where('status', 'terverifikasi')->where('porsi_bayar', 100)->isNotEmpty();

                if (!$isRoomFullPaid) {
                    // Jika membayar porsi 50% - 50%, kedua penghuni harus sudah melunasi biaya awal
                    $roommateHasVerified = $roommate->pembayaran->where('status', 'terverifikasi')->isNotEmpty();
                    if (!$roommateHasVerified) {
                        $roommateUnpaidAwal = true;
                        $roommateName = $roommate->penghuni->nama ?? 'Rekan Sekamar';
                    }
                }
            }
        }

        if (!$hasVerifiedPayment) {
            return [
                'status' => 'belum_bayar_awal',
                'label' => 'Belum Bayar Biaya Awal',
                'badge_class' => 'bg-red-100 text-red-800 dark:bg-red-950/60 dark:text-red-300 border border-red-200 dark:border-red-900/50',
                'can_use_room' => false,
                'unpaid_type' => 'self',
                'roommate_name' => $roommateName,
            ];
        }

        // Jika diri sendiri sudah membayar biaya awal, tetapi di kamar berdua rekan sekamar belum membayar biaya awal (50%)
        if ($isBerbagi2Orang && $roommateUnpaidAwal) {
            return [
                'status' => 'sudah_membayar',
                'label' => 'Sudah Membayar',
                'badge_class' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-900/50',
                'can_use_room' => false,
                'unpaid_type' => 'roommate',
                'roommate_name' => $roommateName,
            ];
        }

        // 2. Jika biaya awal kamar sudah terpenuhi:
        // Cek apakah tagihan perpanjangan sudah keluar
        // Tagihan perpanjangan keluar jika:
        // - Ada pembayaran dengan status 'pending' yang bukan pembayaran awal yang telah diverifikasi
        // - ATAU tanggal sewa saat ini telah mencapai H-7 (hari keluarnya perpanjangan) atau telah jatuh tempo
        $firstVerifiedPayment = $pembayarans->where('status', 'terverifikasi')->sortBy('id')->first();
        $firstVerifiedPaymentId = $firstVerifiedPayment ? $firstVerifiedPayment->id : null;

        $hasPendingRenewal = $pembayarans
            ->where('status', 'pending')
            ->where('id', '!=', $firstVerifiedPaymentId)
            ->isNotEmpty();

        $today = \Carbon\Carbon::now()->startOfDay();
        $tglKeluar = $this->tanggal_keluar ? \Carbon\Carbon::parse($this->tanggal_keluar)->startOfDay() : null;
        $sisaHari = $tglKeluar ? (int) $today->diffInDays($tglKeluar, false) : 999;

        if ($hasPendingRenewal || $sisaHari <= 7) {
            return [
                'status' => 'belum_bayar_perpanjangan',
                'label' => 'Belum Bayar Perpanjangan',
                'badge_class' => 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-900/50',
                'can_use_room' => true,
                'unpaid_type' => null,
                'roommate_name' => '',
            ];
        }

        // 3. Jika biaya awal sudah dibayar dan belum memasuki waktu keluarnya tagihan perpanjangan
        return [
            'status' => 'sudah_membayar',
            'label' => 'Sudah Membayar',
            'badge_class' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-900/50',
            'can_use_room' => true,
            'unpaid_type' => null,
            'roommate_name' => '',
        ];
    }
}
