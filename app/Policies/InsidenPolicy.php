<?php

namespace App\Policies;

use App\Models\Insiden;
use App\Models\User;

class InsidenPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('insiden.lihat');
    }

    public function view(User $user, Insiden $insiden): bool
    {
        return $user->hasPermissionTo('insiden.lihat');
    }

    public function kelola(User $user, Insiden $insiden): bool
    {
        return $user->hasPermissionTo('insiden.kelola');
    }

    /** Write-off aset terkait insiden tetap butuh izin write_off (default: Owner). */
    public function writeOff(User $user, Insiden $insiden): bool
    {
        return $user->hasPermissionTo('write_off');
    }
}
