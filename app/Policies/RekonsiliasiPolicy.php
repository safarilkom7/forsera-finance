<?php

namespace App\Policies;

use App\Models\Rekonsiliasi;
use App\Models\User;

class RekonsiliasiPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isOwner() || $user->isAdmin();
    }

    public function view(User $user, Rekonsiliasi $rekonsiliasi): bool
    {
        return $user->isOwner() || $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isOwner() || $user->isAdmin();
    }

    public function batal(User $user, Rekonsiliasi $rekonsiliasi): bool
    {
        return $user->isOwner();
    }
}
