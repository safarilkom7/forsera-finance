<?php

namespace App\Policies;

use App\Models\Insiden;
use App\Models\User;

class InsidenPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isOwner() || $user->isAdmin();
    }

    public function view(User $user, Insiden $insiden): bool
    {
        return $user->isOwner() || $user->isAdmin();
    }

    public function kelola(User $user, Insiden $insiden): bool
    {
        return $user->isOwner() || $user->isAdmin();
    }

    public function writeOff(User $user, Insiden $insiden): bool
    {
        return $user->isOwner();
    }
}
