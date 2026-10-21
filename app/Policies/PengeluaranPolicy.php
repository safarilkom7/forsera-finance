<?php

namespace App\Policies;

use App\Models\Pengeluaran;
use App\Models\User;

class PengeluaranPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Pengeluaran $pengeluaran): bool
    {
        return $user->isOwner() || $user->isAdmin() || $user->bolehAksesUnit($pengeluaran->unit_usaha_id);
    }

    public function create(User $user): bool
    {
        return $user->isOwner() || $user->isAdmin() || $user->isStaf();
    }

    public function update(User $user, Pengeluaran $pengeluaran): bool
    {
        if (! $this->view($user, $pengeluaran) || $pengeluaran->rekonsiliasi_id !== null) {
            return false;
        }

        if ($pengeluaran->butuhPersetujuan()) {
            return $user->isOwner();
        }

        return $user->isOwner() || $user->isAdmin() || $pengeluaran->dibuat_oleh === $user->id;
    }

    /** Penghapusan transaksi sementara dibatasi ke Owner. */
    public function delete(User $user, Pengeluaran $pengeluaran): bool
    {
        return $user->isOwner()
            && $this->view($user, $pengeluaran)
            && $pengeluaran->rekonsiliasi_id === null;
    }

    public function setujui(User $user, Pengeluaran $pengeluaran): bool
    {
        return $user->isOwner() && $this->view($user, $pengeluaran);
    }
}
