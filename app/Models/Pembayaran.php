<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[\Illuminate\Database\Eloquent\Attributes\Fillable(['id_sppt', 'tgl_bayar', 'jumlah_bayar', 'id_petugas'])]
class Pembayaran extends Model
{
    use HasFactory;

    protected $table = 'pembayaran';
    protected $primaryKey = 'id_bayar';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $casts = [
        'tgl_bayar' => 'datetime',
        'jumlah_bayar' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public function sppt()
    {
        return $this->belongsTo(Sppt::class, 'id_sppt', 'id_sppt');
    }

    public function petugas()
    {
        return $this->belongsTo(User::class, 'id_petugas');
    }
}
