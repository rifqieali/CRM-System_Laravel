<?php

namespace App\Policies;

use App\Models\Contact;
use App\Models\User;
use App\Support\Ownership;

class ContactPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view-any-contact');
    }

    public function view(User $user, Contact $contact): bool
    {
        return $user->can('view-contact') && Ownership::check($user, $contact);
    }

    public function create(User $user): bool
    {
        return $user->can('create-contact');
    }

    public function update(User $user, Contact $contact): bool
    {
        return $user->can('update-contact') && Ownership::check($user, $contact);
    }

    public function delete(User $user, Contact $contact): bool
    {
        return $user->can('delete-contact') && Ownership::check($user, $contact);
    }
}
