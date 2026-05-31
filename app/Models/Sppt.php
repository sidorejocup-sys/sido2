<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[\Illuminate\Database\Eloquent\Attributes\Fillable(['nop', 'tahun', 'njop_bumi', 'njop_bangunan', 'pajak_terhutang', 'status_bayar'])]
class Sppt extends Model
{
    use HasFactory;

    protected $table = 'sppt';
    protected $primaryKey = 'id_sppt';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $casts = [
        'tahun' => 'integer',
        'njop_bumi' => 'decimal:2',
        'njop_bangunan' => 'decimal:2',
        'pajak_terhutang' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function objekPajak()
    {
        return $this->belongsTo(ObjekPajak::class, 'nop', 'nop');
    }

    public function pembayaran()
    {
        return $this->hasMany(Pembayaran::class, 'id_sppt', 'id_sppt');
    }
}
