<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[\Illuminate\Database\Eloquent\Attributes\Fillable(['nop_asal', 'nik_lama', 'nik_baru', 'jenis_mutasi', 'tgl_mutasi', 'no_arsip'])]
class RiwayatMutasi extends Model
{
    use HasFactory;

    protected $table = 'riwayat_mutasi';
    protected $primaryKey = 'id_mutasi';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $casts = [
        'tgl_mutasi' => 'date',
        'created_at' => 'datetime',
    ];
}
