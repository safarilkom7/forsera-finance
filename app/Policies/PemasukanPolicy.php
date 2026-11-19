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

        // Data terkunci hanya lewat koreksi khusus (izinkan lewat koreksi.terkunci).
        if ($pemasukan->terkunci()) {
            return $user->hasPermissionTo('koreksi.terkunci');
        }

        return $user->isOwner() || $user->isAdmin() || $pemasukan->dibuat_oleh === $user->id;
    }

    /** Penghapusan transaksi butuh izin hapus (default: hanya Owner yang memilikinya). */
    public function delete(User $user, Pemasukan $pemasukan): bool
    {
        return $user->hasPermissionTo('pemasukan.hapus')
            && $this->view($user, $pemasukan)
            && ! $pemasukan->terkunci()
            && $pemasukan->rekonsiliasi_id === null;
    }

    public function koreksi(User $user, Pemasukan $pemasukan): bool
    {
        return $user->hasPermissionTo('koreksi.terkunci') && $this->view($user, $pemasukan);
    }
}
