<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;
use App\Support\Ownership;

class CompanyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view-any-company');
    }

    public function view(User $user, Company $company): bool
    {
        return $user->can('view-company') && Ownership::check($user, $company);
    }

    public function create(User $user): bool
    {
        return $user->can('create-company');
    }

    public function update(User $user, Company $company): bool
    {
        return $user->can('update-company') && Ownership::check($user, $company);
    }

    public function delete(User $user, Company $company): bool
    {
        return $user->can('delete-company') && Ownership::check($user, $company);
    }
}
