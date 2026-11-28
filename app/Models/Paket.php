<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Paket extends Model
{
    protected $table = 'paket';

    protected $fillable = [
        'jenis_layanan_id',
        'harga_dasar',
        'satuan',
        'tipe',
        'deskripsi',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'harga_dasar' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<JenisLayanan, $this> */
    public function jenisLayanan(): BelongsTo
    {
        return $this->belongsTo(JenisLayanan::class, 'jenis_layanan_id');
    }

    /** @return HasMany<ProjectItem, $this> */
    public function projectItems(): HasMany
    {
        return $this->hasMany(ProjectItem::class, 'paket_id');
    }
}
