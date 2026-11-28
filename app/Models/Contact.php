<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contact extends Model
{
    protected $table = 'contact';

    protected $fillable = [
        'klien_id',
        'nama',
        'jabatan',
        'nomor_telepon',
        'email',
    ];

    /**
     * @param  Builder<Contact>  $query
     * @return Builder<Contact>
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasRole('owner') || $user->hasRole('staff')) {
            return $query;
        }

        if ($user->hasRole('klien') && $user->contact_id !== null) {
            return $query->whereKey($user->contact_id);
        }

        return $query->whereRaw('1 = 0');
    }

    /** @return BelongsTo<Klien, $this> */
    public function klien(): BelongsTo
    {
        return $this->belongsTo(Klien::class, 'klien_id');
    }

    /** @return HasMany<User, $this> */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'contact_id');
    }

    /** @return BelongsToMany<Project, $this> */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_contact', 'contact_id', 'project_id')
            ->withPivot('peran');
    }
}
