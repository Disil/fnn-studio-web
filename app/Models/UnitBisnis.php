<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UnitBisnis extends Model
{
    protected $table = 'unit_bisnis';

    protected $fillable = [
        'nama_unit',
        'lokasi',
    ];

    /** @return HasMany<JenisLayanan, $this> */
    public function jenisLayanan(): HasMany
    {
        return $this->hasMany(JenisLayanan::class, 'unit_id');
    }

    /** @return HasMany<User, $this> */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'unit_id');
    }
}
