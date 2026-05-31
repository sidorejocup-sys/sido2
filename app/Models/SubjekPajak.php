<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[\Illuminate\Database\Eloquent\Attributes\Fillable(['NIK', 'nama', 'alamat', 'RT', 'RW', 'no_hp'])]
class SubjekPajak extends Model
{
    use HasFactory;

    protected $table = 'subjek_pajak';
    protected $primaryKey = 'NIK';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function objekPajaks()
    {
        return $this->hasMany(ObjekPajak::class, 'nik_pemilik', 'NIK');
    }
}
