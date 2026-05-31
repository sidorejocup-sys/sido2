<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[\Illuminate\Database\Eloquent\Attributes\Fillable(['nop', 'nik_pemilik', 'letak_objek', 'luas_bumi', 'luas_bangunan', 'status_aktif'])]
class ObjekPajak extends Model
{
    use HasFactory;

    protected $table = 'objek_pajak';
    protected $primaryKey = 'nop';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $casts = [
        'luas_bumi' => 'integer',
        'luas_bangunan' => 'integer',
        'status_aktif' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function subjekPajak()
    {
        return $this->belongsTo(SubjekPajak::class, 'nik_pemilik', 'NIK');
    }

    public function sppts()
    {
        return $this->hasMany(Sppt::class, 'nop', 'nop');
    }
}
