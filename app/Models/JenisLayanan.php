<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JenisLayanan extends Model
{
    protected $table = 'jenis_layanan';

    protected $fillable = [
        'unit_id',
        'kode',
        'nama',
    ];

    /** @return BelongsTo<UnitBisnis, $this> */
    public function unitBisnis(): BelongsTo
    {
        return $this->belongsTo(UnitBisnis::class, 'unit_id');
    }

    /** @return HasMany<Paket, $this> */
    public function pakets(): HasMany
    {
        return $this->hasMany(Paket::class, 'jenis_layanan_id');
    }
}
