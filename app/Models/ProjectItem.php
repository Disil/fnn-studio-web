<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectItem extends Model
{
    protected $table = 'project_item';

    protected $fillable = [
        'project_id',
        'paket_id',
        'tipe_item',
        'kuantitas',
        'harga_satuan_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'kuantitas' => 'integer',
            'harga_satuan_snapshot' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /** @return BelongsTo<Paket, $this> */
    public function paket(): BelongsTo
    {
        return $this->belongsTo(Paket::class, 'paket_id');
    }
}
