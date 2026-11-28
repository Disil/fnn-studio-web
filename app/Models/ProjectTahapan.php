<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectTahapan extends Model
{
    protected $table = 'project_tahapan';

    protected $fillable = [
        'project_id',
        'nama_tahap',
        'urutan',
        'deadline_tahap',
        'selesai',
    ];

    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
            'deadline_tahap' => 'date',
            'selesai' => 'boolean',
        ];
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }
}
