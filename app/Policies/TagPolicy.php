<?php

namespace App\Policies;

use App\Models\Tag;
use App\Models\User;

class TagPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view-any-tag');
    }

    public function view(User $user, Tag $tag): bool
    {
        return $user->can('view-tag');
    }

    public function create(User $user): bool
    {
        return $user->can('create-tag');
    }

    public function update(User $user, Tag $tag): bool
    {
        return $user->can('update-tag');
    }

    public function delete(User $user, Tag $tag): bool
    {
        return $user->can('delete-tag');
    }
}
