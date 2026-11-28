<?php

namespace App\Policies;

use App\Models\Contact;
use App\Models\User;

class ContactPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('owner') || $user->hasRole('staff');
    }

    public function view(User $user, Contact $contact): bool
    {
        return Contact::query()
            ->visibleTo($user)
            ->whereKey($contact->getKey())
            ->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasRole('owner') || $user->hasRole('staff');
    }

    public function update(User $user, Contact $contact): bool
    {
        return ($user->hasRole('owner') || $user->hasRole('staff'))
            && $this->view($user, $contact);
    }

    public function delete(User $user, Contact $contact): bool
    {
        return ($user->hasRole('owner') || $user->hasRole('staff'))
            && $this->view($user, $contact);
    }
}
