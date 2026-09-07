<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait ScopedToUser
{
    public function scopeScopedTo(Builder $query, User $user): Builder
    {
        if ($user->hasAnyRole(['Admin', 'Manager'])) {
            return $query;
        }

        $query->where(function (Builder $q) use ($user) {
            $q->where('owner_id', $user->id);

            if ($user->manager_id !== null) {
                $q->orWhereIn('owner_id', function ($sub) use ($user) {
                    $sub->select('id')
                        ->from('users')
                        ->where('manager_id', $user->manager_id);
                });
            }
        });

        return $query;
    }
}
