<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectLog extends Model
{
    protected $table = 'project_logs';

    public $timestamps = false;

    protected $fillable = [
        'project_id',
        'status_lama',
        'status_baru',
        'diubah_oleh',
    ];

    protected function casts(): array
    {
        return [
            'diubah_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /** @return BelongsTo<User, $this> */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diubah_oleh');
    }
}
