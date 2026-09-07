<?php

namespace App\Policies;

use App\Models\Note;
use App\Models\User;
use App\Support\Ownership;

class NotePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view-any-note');
    }

    public function view(User $user, Note $note): bool
    {
        if (! $user->can('view-note')) {
            return false;
        }

        $parent = $note->noteable;

        if ($parent === null) {
            return false;
        }

        return Ownership::check($user, $parent);
    }

    public function create(User $user): bool
    {
        return $user->can('create-note');
    }

    public function update(User $user, Note $note): bool
    {
        if (! $user->can('update-note')) {
            return false;
        }

        if ($note->user_id === $user->id) {
            return true;
        }

        $parent = $note->noteable;

        if ($parent === null) {
            return false;
        }

        return Ownership::check($user, $parent);
    }

    public function delete(User $user, Note $note): bool
    {
        return $this->update($user, $note);
    }
}
