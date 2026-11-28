<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $status
 */
class Project extends Model
{
    protected $table = 'project';

    protected $fillable = [
        'klien_id',
        'dibuat_oleh',
        'status',
        'sumber',
        'lokasi',
        'tanggal_mulai',
        'tanggal_selesai',
        'deadline',
        'nomor_po',
        'tanggal_po',
        'link_dokumen_po',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'deadline' => 'date',
            'tanggal_po' => 'date',
        ];
    }

    /** @return Attribute<string, never> */
    protected function totalTagihan(): Attribute
    {
        return Attribute::get(
            fn (): string => (string) $this->projectItems()
                ->selectRaw('COALESCE(SUM(kuantitas * harga_satuan_snapshot), 0) AS total')
                ->value('total'),
        );
    }

    /** @return Attribute<string, never> */
    protected function totalTerbayar(): Attribute
    {
        return Attribute::get(
            fn (): string => (string) $this->payments()
                ->selectRaw('COALESCE(SUM(jumlah), 0) AS total')
                ->value('total'),
        );
    }

    /**
     * @param  Builder<Project>  $query
     * @return Builder<Project>
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasRole('owner')) {
            return $query;
        }

        if ($user->hasRole('freelancer')) {
            return $query->whereHas(
                'crew',
                fn (Builder $crew): Builder => $crew->where('user.id', $user->getKey()),
            );
        }

        if ($user->hasRole('klien')) {
            if ($user->contact_id === null) {
                return $query->whereRaw('1 = 0');
            }

            return $query
                ->whereHas(
                    'contacts',
                    fn (Builder $contacts): Builder => $contacts->where('contact.id', $user->contact_id),
                )
                ->select([
                    'project.id',
                    'project.status',
                    'project.tanggal_mulai',
                    'project.tanggal_selesai',
                    'project.deadline',
                    'project.updated_at',
                ]);
        }

        if (! $user->hasRole('staff') || $user->unit_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query
            ->whereHas('projectItems')
            ->whereDoesntHave(
                'projectItems.paket.jenisLayanan',
                fn (Builder $services): Builder => $services->where('unit_id', '!=', $user->unit_id),
            );
    }

    /** @return BelongsTo<Klien, $this> */
    public function klien(): BelongsTo
    {
        return $this->belongsTo(Klien::class, 'klien_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    /** @return HasMany<ProjectItem, $this> */
    public function projectItems(): HasMany
    {
        return $this->hasMany(ProjectItem::class, 'project_id');
    }

    /** @return BelongsToMany<Contact, $this> */
    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class, 'project_contact', 'project_id', 'contact_id')
            ->withPivot('peran');
    }

    /** @return BelongsToMany<User, $this> */
    public function crew(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_crew', 'project_id', 'user_id')
            ->withPivot('peran');
    }

    /** @return HasMany<ProjectTahapan, $this> */
    public function tahapan(): HasMany
    {
        return $this->hasMany(ProjectTahapan::class, 'project_id');
    }

    /** @return HasMany<Deliverable, $this> */
    public function deliverables(): HasMany
    {
        return $this->hasMany(Deliverable::class, 'project_id');
    }

    /** @return HasMany<ProjectRevision, $this> */
    public function revisions(): HasMany
    {
        return $this->hasMany(ProjectRevision::class, 'project_id');
    }

    /** @return HasMany<Kendala, $this> */
    public function kendala(): HasMany
    {
        return $this->hasMany(Kendala::class, 'project_id');
    }

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'project_id');
    }

    /** @return HasMany<ProjectLog, $this> */
    public function logs(): HasMany
    {
        return $this->hasMany(ProjectLog::class, 'project_id');
    }
}
