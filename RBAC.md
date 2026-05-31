# Role-Based Access Control (RBAC) & Authentication

This document outlines the authentication system, role-based access control, and authorization patterns used in the Sido application.

## Authentication Flow

1. **Root Redirect** (`/`): Unauthenticated users are redirected to `/login`.
2. **Login** (`POST /login`): Users submit credentials; passwords are verified via bcrypt.
3. **Dashboard Redirect** (`/dashboard`): After login, users are redirected to their role-specific dashboard.
4. **Logout** (`POST /logout`): Clears session and redirects to login.

## Roles and Permissions

### 1. super_admin

**Scope**: Full system access.

**Permissions**:
- View all SPPTs, payments, and audit trails
- Create, update, delete SPPTs and payments
- Import/Export data (CSV, Excel)
- Approve payment proposals
- Manage configurations
- View system-wide statistics and financial metrics

**Dashboard**: `/admin/dashboard` — Full system overview with all metrics.

**Routes**:
- `GET /admin/dashboard`
- `POST /admin/import`
- `POST /admin/export`
- `POST /admin/approve-payment/{pembayaran}`
- `POST /pembayaran` (create)
- `PUT /pembayaran/{pembayaran}` (update)
- `DELETE /pembayaran/{pembayaran}` (delete)

**Authorization**: `auth()->user()->role === 'super_admin'`

---

### 2. kades

**Scope**: Village head with access to village-level data.

**Permissions**:
- View all SPPTs and payments within the village
- Filter data by RT/RW
- View village statistics and collection rates
- Access payment tracking
- View audit logs for transactions within village

**Dashboard**: `/village/dashboard` — Village-scoped overview.

**Routes**:
- `GET /village/dashboard`
- `GET /village/payments`
- `GET /village/statistics`

**Authorization**: `in_array(auth()->user()->role, ['kades', 'kasun_rw', 'rt'])`

---

### 3. kasun_rw

**Scope**: Sub-village leader with RW-scoped access.

**Permissions**:
- Same as Kades but filtered to RW (Rukun Warga - neighborhood cluster) scope
- Cannot access data outside their assigned RW

**Data Filtering**: `where('RW', auth()->user()->rw)`

---

### 4. rt

**Scope**: Neighborhood leader with RT-scoped access.

**Permissions**:
- Same as Kades but filtered to RT (Rukun Tetangga - community unit) scope
- Cannot access data outside their assigned RT

**Data Filtering**: `where('RT', auth()->user()->rt)`

---

### 5. pengguna (Regular User)

**Scope**: Ultra-restricted access to personal property data only.

**Permissions**:
- View ONLY their own SPPT bills (matched by NIK)
- Submit payment proposals (change status from `piutang` to `proses_pengajuan`)
- NO access to delete/edit data
- NO visibility of village or global financial metrics
- NO access to other users' data

**Dashboard**: `/user/dashboard` — Minimal, user-focused dashboard.

**Routes**:
- `GET /user/dashboard`
- `GET /user/sppt` (own bills only)
- `POST /user/sppt/{sppt}/payment-proposal` (submit proposal only)

**Data Immutability**: Users cannot update or delete records. All data is read-only.

**Authorization**: `auth()->user()->role === 'pengguna'`

---

## Authorization Implementation

### Gates (in AppServiceProvider)

Gates define boolean permissions:

```php
// Super admin gate
Gate::define('admin', fn(User $user) => $user->role === 'super_admin');

// Village dashboard access
Gate::define('view-village-dashboard', fn(User $user) => 
    in_array($user->role, ['super_admin', 'kades', 'kasun_rw', 'rt'])
);

// Payment approval
Gate::define('approve-payment', fn(User $user) => 
    $user->role === 'super_admin'
);
```

### Policies (in Policies/)

Policies handle model-specific authorization:

```php
class SpptPolicy {
    public function view(User $user, Sppt $sppt): bool {
        if ($user->role === 'super_admin') return true;
        if ($user->role === 'pengguna') return false; // Access via ObjekPajak
        return in_array($user->role, ['kades', 'kasun_rw', 'rt']);
    }

    public function update(User $user, Sppt $sppt): bool {
        return $user->role === 'super_admin';
    }
}
```

### CheckRole Middleware

Restricts routes to specific roles:

```php
Route::middleware('role:super_admin')->group(function () {
    // Only super_admin can access
});

Route::middleware('role:kades,kasun_rw,rt')->group(function () {
    // Only these roles can access
});
```

---

## Controller Examples

### AdminDashboardController

```php
public function index() {
    $this->authorize('admin'); // Gate check
    // Show full system metrics
}

public function approvePayment(Pembayaran $pembayaran) {
    $this->authorize('admin');
    $this->authorize('approve-payment'); // Gate check
    // Update payment status
}
```

### VillageDashboardController

```php
public function index() {
    $this->authorize('view-village-dashboard'); // Gate check
    $rt = auth()->user()->rt;
    $rw = auth()->user()->rw;
    // Filter data by user's RT/RW
}
```

### UserDashboardController

```php
public function mySppt() {
    if (auth()->user()->role !== 'pengguna') {
        abort(403, 'Unauthorized');
    }
    $nik = auth()->user()->nik;
    // Return ONLY user's own SPPTs matched by NIK
}

public function submitPaymentProposal(Request $request, Sppt $sppt) {
    // Verify ownership via NIK
    $ownedByUser = $sppt->objekPajak->nik_pemilik === auth()->user()->nik;
    if (!$ownedByUser) abort(403);
    
    // Change status to 'proses_pengajuan'
}
```

---

## User Table Extensions

The `users` table has been extended with geographic and property references:

```php
$table->string('nik', 16)->nullable();  // National ID (for regular users)
$table->string('rt', 3)->nullable();    // Rukun Tetangga (for RT leaders)
$table->string('rw', 3)->nullable();    // Rukun Warga (for RW leaders)
```

---

## Blade Template Examples

### Show admin-only content

```blade
@can('admin')
    <div>Admin controls here</div>
@endcan
```

### Show village-scoped content

```blade
@can('view-village-dashboard')
    <div>Village dashboard content</div>
@endcan
```

### Hide sensitive data from regular users

```blade
@unless(auth()->user()->role === 'pengguna')
    <div>Financial metrics (hidden from pengguna)</div>
@endunless
```

---

## Testing RBAC

### Test super_admin access

```php
$user = User::factory()->create(['role' => 'super_admin']);
$this->actingAs($user)->get('/admin/dashboard')->assertStatus(200);
```

### Test pengguna restrictions

```php
$user = User::factory()->create(['role' => 'pengguna']);
$this->actingAs($user)->get('/admin/dashboard')->assertStatus(403);
```

---

## Routing Summary

| Route | Method | Auth | Role | Purpose |
|-------|--------|------|------|---------|
| `/login` | GET | No | - | Show login form |
| `/login` | POST | No | - | Handle login (rate-limited) |
| `/logout` | POST | Yes | All | Logout |
| `/dashboard` | GET | Yes | All | Redirect to role-specific dashboard |
| `/admin/dashboard` | GET | Yes | super_admin | Admin dashboard |
| `/admin/import` | POST | Yes | super_admin | Import data |
| `/admin/export` | POST | Yes | super_admin | Export data |
| `/admin/approve-payment/{id}` | POST | Yes | super_admin | Approve payment |
| `/village/dashboard` | GET | Yes | kades, kasun_rw, rt | Village dashboard |
| `/village/payments` | GET | Yes | kades, kasun_rw, rt | View payments |
| `/village/statistics` | GET | Yes | kades, kasun_rw, rt | View statistics |
| `/user/dashboard` | GET | Yes | pengguna | User dashboard |
| `/user/sppt` | GET | Yes | pengguna | View own SPPTs |
| `/user/sppt/{id}/payment-proposal` | POST | Yes | pengguna | Submit payment proposal |
| `/pembayaran` | POST | Yes | super_admin | Create payment |
| `/pembayaran/{id}` | PUT | Yes | super_admin | Update payment |
| `/pembayaran/{id}` | DELETE | Yes | super_admin | Delete payment |

---

## Security Checklist

- [ ] All routes are protected with authentication or role middleware
- [ ] Sensitive routes (admin, approval) require super_admin role
- [ ] Regular users cannot access admin functions
- [ ] Regular users see only their own data
- [ ] Geographic filtering (RT/RW) is applied in queries
- [ ] Data immutability enforced for regular users
- [ ] All write operations wrap in `DB::transaction()`
- [ ] Authorization is checked in both middleware and controller
- [ ] Audit logging captures all sensitive operations
