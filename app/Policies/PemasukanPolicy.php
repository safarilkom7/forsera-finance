<?php

namespace App\Policies;

use App\Models\Pemasukan;
use App\Models\User;

class PemasukanPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Pemasukan $pemasukan): bool
    {
        return $user->isOwner() || $user->isAdmin() || $user->bolehAksesUnit($pemasukan->unit_usaha_id);
    }

    public function create(User $user): bool
    {
        return $user->isOwner() || $user->isAdmin() || $user->isStaf();
    }

    public function update(User $user, Pemasukan $pemasukan): bool
    {
        if (! $this->view($user, $pemasukan)) {
            return false;
        }

        if ($pemasukan->terkunci()) {
            return $user->isOwner();
        }

        return $user->isOwner() || $user->isAdmin() || $pemasukan->dibuat_oleh === $user->id;
    }

    public function delete(User $user, Pemasukan $pemasukan): bool
    {
        return $this->update($user, $pemasukan) && ! $pemasukan->terkunci() && $pemasukan->rekonsiliasi_id === null;
    }

    public function koreksi(User $user, Pemasukan $pemasukan): bool
    {
        return $user->isOwner() && $this->view($user, $pemasukan);
    }
}
