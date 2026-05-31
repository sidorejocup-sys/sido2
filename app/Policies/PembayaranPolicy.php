<?php

namespace App\Policies;

use App\Models\Pembayaran;
use App\Models\User;

class PembayaranPolicy
{
    /**
     * Determine whether the user can view the payment.
     */
    public function view(User $user, Pembayaran $pembayaran): bool
    {
        // Super admin can view all
        if ($user->role === 'super_admin') {
            return true;
        }

        // Regular users cannot view payments (restricted to SPPT view)
        if ($user->role === 'pengguna') {
            return false;
        }

        // Kades, Kasun RW, RT can view all
        return in_array($user->role, ['kades', 'kasun_rw', 'rt']);
    }

    /**
     * Determine whether the user can create payments.
     */
    public function create(User $user): bool
    {
        // Only super admin can create payments directly
        return $user->role === 'super_admin';
    }

    /**
     * Determine whether the user can update the payment.
     */
    public function update(User $user, Pembayaran $pembayaran): bool
    {
        // Only super admin can update
        return $user->role === 'super_admin';
    }

    /**
     * Determine whether the user can delete the payment.
     */
    public function delete(User $user, Pembayaran $pembayaran): bool
    {
        // Only super admin can delete
        return $user->role === 'super_admin';
    }
}
