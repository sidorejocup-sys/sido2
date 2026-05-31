<?php

namespace App\Policies;

use App\Models\Sppt;
use App\Models\User;

class SpptPolicy
{
    /**
     * Determine whether the user can view the SPPT.
     */
    public function view(User $user, Sppt $sppt): bool
    {
        // Super admin can view all
        if ($user->role === 'super_admin') {
            return true;
        }

        // Regular users can only view their own SPPTs
        if ($user->role === 'pengguna') {
            return false; // Users access through ObjekPajak → SubjekPajak
        }

        // Kades, Kasun RW, RT can view all in their scope
        return in_array($user->role, ['kades', 'kasun_rw', 'rt']);
    }

    /**
     * Determine whether the user can create SPPTs.
     */
    public function create(User $user): bool
    {
        return $user->role === 'super_admin';
    }

    /**
     * Determine whether the user can update the SPPT.
     */
    public function update(User $user, Sppt $sppt): bool
    {
        // Only super admin can update
        return $user->role === 'super_admin';
    }

    /**
     * Determine whether the user can delete the SPPT.
     */
    public function delete(User $user, Sppt $sppt): bool
    {
        // Only super admin can delete
        return $user->role === 'super_admin';
    }
}
