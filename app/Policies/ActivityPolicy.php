<?php

namespace App\Policies;

use App\Models\Activity;
use App\Models\User;
use App\Support\Ownership;

class ActivityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view-any-activity');
    }

    public function view(User $user, Activity $activity): bool
    {
        if (! $user->can('view-activity')) {
            return false;
        }

        $parent = $activity->activityable;

        if ($parent === null) {
            return false;
        }

        return Ownership::check($user, $parent);
    }

    public function create(User $user): bool
    {
        return $user->can('create-activity');
    }

    public function update(User $user, Activity $activity): bool
    {
        if (! $user->can('update-activity')) {
            return false;
        }

        if ($activity->user_id === $user->id) {
            return true;
        }

        $parent = $activity->activityable;

        if ($parent === null) {
            return false;
        }

        return Ownership::check($user, $parent);
    }

    public function delete(User $user, Activity $activity): bool
    {
        return $this->update($user, $activity);
    }
}
