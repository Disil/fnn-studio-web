<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $project_id
 */
class Payment extends Model
{
    protected $table = 'payment';

    protected $fillable = [
        'project_id',
        'dicatat_oleh',
        'jumlah',
        'metode',
        'tanggal_bayar',
        'bukti',
        'catatan',
    ];

    protected function casts(): array
    {
        return [
            'jumlah' => 'decimal:2',
            'tanggal_bayar' => 'date',
        ];
    }

    /**
     * @param  Builder<Payment>  $query
     * @return Builder<Payment>
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasRole('owner')) {
            return $query;
        }

        if (! $user->hasRole('staff')) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas(
            'project',
            fn (Builder $projects): Builder => $projects->visibleTo($user),
        );
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /** @return BelongsTo<User, $this> */
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicatat_oleh');
    }
}
