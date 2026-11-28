<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\Project;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('owner') || $user->hasRole('staff');
    }

    public function view(User $user, Payment $payment): bool
    {
        if (! $this->viewAny($user)) {
            return false;
        }

        return Project::query()
            ->visibleTo($user)
            ->whereKey($payment->project_id)
            ->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasRole('owner') || $user->hasRole('staff');
    }

    public function createForProject(User $user, Project $project): bool
    {
        return $this->create($user)
            && Project::query()
                ->visibleTo($user)
                ->whereKey($project->getKey())
                ->exists();
    }

    public function update(User $user, Payment $payment): bool
    {
        return $this->view($user, $payment);
    }

    public function delete(User $user, Payment $payment): bool
    {
        return $this->view($user, $payment);
    }
}
