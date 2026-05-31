<?php

namespace App\Providers;

use App\Models\Pembayaran;
use App\Models\Sppt;
use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Sppt::class => \App\Policies\SpptPolicy::class,
        Pembayaran::class => \App\Policies\PembayaranPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Super admin can do anything
        Gate::define('admin', function (User $user) {
            return $user->role === 'super_admin';
        });

        // Kades, Kasun RW, and RT can access village dashboards
        Gate::define('view-village-dashboard', function (User $user) {
            return in_array($user->role, ['super_admin', 'kades', 'kasun_rw', 'rt']);
        });

        // Only super admin can manage all configurations
        Gate::define('manage-config', function (User $user) {
            return $user->role === 'super_admin';
        });

        // Only super admin can import/export data
        Gate::define('import-export', function (User $user) {
            return $user->role === 'super_admin';
        });

        // Only super admin can approve payments
        Gate::define('approve-payment', function (User $user) {
            return $user->role === 'super_admin';
        });

        // Kades, Kasun RW, RT can view payments in their scope
        Gate::define('view-scoped-payments', function (User $user) {
            return in_array($user->role, ['super_admin', 'kades', 'kasun_rw', 'rt']);
        });

        // Regular users can only view their own data
        Gate::define('view-own-sppt', function (User $user) {
            return true; // All authenticated users
        });

        // Regular users can submit payment proposals
        Gate::define('submit-payment-proposal', function (User $user) {
            return $user->role === 'pengguna';
        });
    }
}
