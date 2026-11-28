<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Deliverable extends Model
{
    protected $table = 'deliverable';

    public $timestamps = false;

    protected $fillable = [
        'project_id',
        'versi',
        'judul',
        'link',
        'tipe',
        'tahap',
        'tampil_ke_klien',
        'tanggal_kirim',
    ];

    protected function casts(): array
    {
        return [
            'versi' => 'integer',
            'tampil_ke_klien' => 'boolean',
            'tanggal_kirim' => 'datetime',
        ];
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }
}
