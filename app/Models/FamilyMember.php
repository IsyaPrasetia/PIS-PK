<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FamilyMember extends Model
{
    protected $fillable = [
        'family_id',
        'nama',
        'umur',
        'jenis_kelamin',
        'hubungan',
        'nik',
    ];

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function jenisKelaminLabel(): string
    {
        return $this->jenis_kelamin === 'P' ? 'Perempuan' : 'Laki-laki';
    }
}
