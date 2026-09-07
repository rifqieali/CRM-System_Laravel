<?php

namespace App\Policies;

use App\Models\Deal;
use App\Models\User;
use App\Support\Ownership;

class DealPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view-any-deal');
    }

    public function view(User $user, Deal $deal): bool
    {
        return $user->can('view-deal') && Ownership::check($user, $deal);
    }

    public function create(User $user): bool
    {
        return $user->can('create-deal');
    }

    public function update(User $user, Deal $deal): bool
    {
        return $user->can('update-deal') && Ownership::check($user, $deal);
    }

    public function delete(User $user, Deal $deal): bool
    {
        return $user->can('delete-deal') && Ownership::check($user, $deal);
    }
}
