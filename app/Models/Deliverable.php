<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property bool $tampil_ke_klien
 */
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

    /**
     * @param  Builder<Deliverable>  $query
     * @return Builder<Deliverable>
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if (
            ! $user->hasRole('owner')
            && ! $user->hasRole('staff')
            && ! $user->hasRole('freelancer')
            && ! $user->hasRole('klien')
        ) {
            return $query->whereRaw('1 = 0');
        }

        $query->whereHas(
            'project',
            fn (Builder $projects): Builder => $projects->visibleTo($user),
        );

        if ($user->hasRole('klien')) {
            return $query->where('tampil_ke_klien', true);
        }

        return $query;
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }
}
