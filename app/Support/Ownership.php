<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Ownership
{
    public static function check(User $user, Model $record): bool
    {
        if ($user->hasAnyRole(['Admin', 'Manager'])) {
            return true;
        }

        $ownerId = $record->owner_id ?? null;

        if ($ownerId === $user->id) {
            return true;
        }

        if ($user->manager_id === null) {
            return false;
        }

        $owner = $record->owner ?? null;

        return $owner !== null
            && $owner->manager_id !== null
            && $owner->manager_id === $user->manager_id;
    }
}
