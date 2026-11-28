<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $username
 * @property string $email
 * @property string $password
 * @property string $role
 * @property string|null $tipe_kontrak
 * @property string|null $remember_token
 */
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $table = 'user';

    protected $fillable = [
        'username',
        'email',
        'password',
        'role',
        'unit_id',
        'contact_id',
        'tipe_kontrak',
        'keahlian',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    /** @return BelongsTo<UnitBisnis, $this> */
    public function unitBisnis(): BelongsTo
    {
        return $this->belongsTo(UnitBisnis::class, 'unit_id');
    }

    /** @return BelongsTo<Contact, $this> */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    /** @return HasMany<Project, $this> */
    public function createdProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'dibuat_oleh');
    }

    /** @return HasMany<Payment, $this> */
    public function recordedPayments(): HasMany
    {
        return $this->hasMany(Payment::class, 'dicatat_oleh');
    }

    /** @return HasMany<ProjectLog, $this> */
    public function projectStatusChanges(): HasMany
    {
        return $this->hasMany(ProjectLog::class, 'diubah_oleh');
    }

    /** @return BelongsToMany<Project, $this> */
    public function crewProjects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_crew', 'user_id', 'project_id')
            ->withPivot('peran');
    }

    public function hasActiveFreelanceProject(): bool
    {
        if ($this->role !== 'staff' || $this->tipe_kontrak !== 'freelance') {
            return false;
        }

        return $this->crewProjects()
            ->whereNotIn('project.status', ['selesai', 'dibatalkan'])
            ->exists();
    }

    public function initials(): string
    {
        $initials = Str::initials($this->username);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }
}
