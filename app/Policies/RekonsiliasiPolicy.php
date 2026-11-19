<?php

namespace App\Policies;

use App\Models\Rekonsiliasi;
use App\Models\User;

class RekonsiliasiPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('rekonsiliasi.lihat');
    }

    public function view(User $user, Rekonsiliasi $rekonsiliasi): bool
    {
        return $user->hasPermissionTo('rekonsiliasi.lihat');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('rekonsiliasi.lihat');
    }

    /** Pembatalan rekonsiliasi tetap khusus Owner. */
    public function batal(User $user, Rekonsiliasi $rekonsiliasi): bool
    {
        return $user->isOwner();
    }
}
