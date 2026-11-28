<?php

namespace App\Policies;

use App\Models\Deliverable;
use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('owner')
            || $user->hasRole('staff')
            || $user->hasRole('freelancer')
            || ($user->hasRole('klien') && $user->contact_id !== null);
    }

    public function view(User $user, Project $project): bool
    {
        return Project::query()
            ->visibleTo($user)
            ->whereKey($project->getKey())
            ->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasRole('owner')
            || ($user->hasRole('staff') && $user->unit_id !== null);
    }

    public function update(User $user, Project $project): bool
    {
        return ($user->hasRole('owner') || $user->hasRole('staff'))
            && $this->view($user, $project);
    }

    public function updateProgress(User $user, Project $project): bool
    {
        return ($user->hasRole('owner') || $user->hasRole('staff') || $user->hasRole('freelancer'))
            && $this->view($user, $project);
    }

    public function viewDeliverable(User $user, Project $project, Deliverable $deliverable): bool
    {
        if (
            ! $deliverable->project()->whereKey($project->getKey())->exists()
            || ! $this->view($user, $project)
        ) {
            return false;
        }

        return ! $user->hasRole('klien') || $deliverable->tampil_ke_klien;
    }

    public function delete(User $user, Project $project): bool
    {
        return ($user->hasRole('owner') || $user->hasRole('staff'))
            && $this->view($user, $project);
    }
}
