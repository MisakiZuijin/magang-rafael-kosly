<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogPopupAturan extends Model
{
    use HasFactory;

    protected $table = 'log_popup_aturan';

    protected $fillable = [
        'penghuni_id',
        'kos_id',
        'tanggal_popup',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_popup' => 'date',
            'created_at' => 'datetime',
        ];
    }

    public function penghuni()
    {
        return $this->belongsTo(User::class, 'penghuni_id');
    }

    public function kos()
    {
        return $this->belongsTo(Kos::class, 'kos_id');
    }
}
