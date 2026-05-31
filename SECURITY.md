# Security Policy & Implementation

This document outlines the security requirements and implementations for the Sido application.

## 1. SQL Injection Prevention

**Implementation**: Eloquent ORM with parameterized bindings.

All database queries must use Eloquent ORM or the query builder with parameter binding. Never concatenate user input directly into queries.

### ✅ Safe Examples

```php
// Eloquent ORM (recommended)
$user = User::where('email', $email)->first();
$users = User::whereIn('id', $ids)->get();

// Query Builder
$users = DB::table('users')
    ->where('email', '=', $email)
    ->get();
```

### ❌ Unsafe Examples

```php
// NEVER use raw string concatenation
$user = DB::select("SELECT * FROM users WHERE email = '$email'"); // DANGEROUS!
```

## 2. XSS (Cross-Site Scripting) Prevention

**Implementation**: Blade escaping and HTML sanitization.

- By default, Blade escapes all `{{ }}` syntax with `htmlspecialchars()`.
- Use `{!! !!}` only for trusted HTML content.
- Sanitize user input on backend using `htmlspecialchars()` or the `Purifier` package.

### ✅ Safe Examples

```blade
<!-- Escaped by default -->
<p>{{ $user->name }}</p>

<!-- For trusted HTML only -->
{!! $trustedContent !!}
```

### ❌ Unsafe Examples

```blade
<!-- NEVER output user input without escaping -->
<p>{!! $user->input !!}</p>
```

### Sanitization Helper

Use the provided `sanitizeHtml()` helper for user input:

```php
$cleanInput = sanitizeHtml($userInput);
```

## 3. CSRF Protection

**Implementation**: Middleware enforced on all POST/PUT/PATCH/DELETE routes.

- All form submissions must include the `@csrf` directive in Blade templates.
- API requests must include the `X-CSRF-Token` header.

### ✅ Safe Examples

```blade
<form method="POST" action="/submit">
    @csrf
    <input type="text" name="data" />
</form>
```

```javascript
// API request with CSRF token
const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
fetch('/api/endpoint', {
    method: 'POST',
    headers: {
        'X-CSRF-Token': token,
        'Content-Type': 'application/json'
    },
    body: JSON.stringify(data)
});
```

## 4. Rate Limiting

**Implementation**: Throttle middleware and custom rate limiting.

### Login Rate Limiting

- **Max 5 login attempts per minute per IP**
- Implemented via `LoginRateLimitMiddleware`
- Returns HTTP 429 (Too Many Requests) when exceeded

### API Rate Limiting

- **Max 60 requests per minute per IP**
- Implemented via `api` rate limit group
- Applies to all `api/*` routes

### ✅ Usage in Routes

```php
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('login.rate.limit');

Route::middleware('api')->group(function () {
    Route::post('/resource', [ApiController::class, 'store']);
});
```

## 5. Database Transactions

**Implementation**: `DB::transaction()` for all write/update operations.

All create, update, and delete operations must wrap multiple database queries in a transaction to prevent race conditions and partial states.

### ✅ Safe Examples

```php
use Illuminate\Support\Facades\DB;

// Single query (auto-wrapped)
User::create($data);

// Multiple queries (explicit transaction)
DB::transaction(function () {
    $user = User::create($data);
    $user->roles()->attach($roleIds);
    Log::info('User created', ['user_id' => $user->id]);
});

// With error handling
try {
    DB::transaction(function () {
        $sppt = Sppt::create($spptData);
        Pembayaran::create(['id_sppt' => $sppt->id_sppt, ...]);
    });
} catch (\Exception $e) {
    Log::error('Transaction failed', ['error' => $e->getMessage()]);
    return response()->json(['error' => 'Database operation failed'], 500);
}
```

### ❌ Unsafe Examples

```php
// NEVER split related operations without a transaction
$sppt = Sppt::create($spptData); // If this succeeds
Pembayaran::create($paymentData); // But this fails, data is inconsistent
```

## 6. Authentication & Authorization

- Role-based access control (RBAC) via `User::role` field.
- Roles: `super_admin`, `kades`, `kasun_rw`, `rt`, `pengguna`.
- Use policies and gates for authorization checks.

```php
// Check authorization
if (auth()->user()->role !== 'super_admin') {
    abort(403, 'Unauthorized');
}

// Or use a policy
$this->authorize('view', $sppt);
```

## 7. Secure Password Storage

- Passwords are hashed using bcrypt via Laravel's `Hash::make()`.
- Never store passwords in plain text.
- Use `Hash::check($password, $hashedPassword)` to verify.

```php
// Creating a user
User::create([
    'email' => 'user@example.com',
    'password' => Hash::make('plaintext_password'),
]);

// Verifying a password
if (Hash::check($attemptedPassword, $user->password)) {
    // Password matches
}
```

## 8. Environment Variables

- Never commit `.env` files or secrets to version control.
- Use `.env.example` for documentation.
- Sensitive values: database passwords, API keys, app key, etc.

## 9. Logging & Audit Trail

- Log all authentication events, role changes, and financial transactions.
- Use `Log::info()`, `Log::warning()`, `Log::error()` appropriately.
- The `riwayat_mutasi` table provides an audit trail for property mutations.

## 10. HTTPS & Security Headers

- Always use HTTPS in production.
- Configure security headers (HSTS, X-Frame-Options, X-Content-Type-Options, etc.).
- Enable in middleware or web server configuration.

## Checklist

- [ ] All database queries use Eloquent ORM or parameterized bindings
- [ ] All user input is escaped when rendered in Blade templates
- [ ] All forms include `@csrf` directive
- [ ] Login attempts are rate-limited to 5 per minute per IP
- [ ] API requests are rate-limited to 60 per minute
- [ ] All write/update operations are wrapped in `DB::transaction()`
- [ ] Passwords are hashed using bcrypt
- [ ] `.env` file is not committed to version control
- [ ] HTTPS is enabled in production
- [ ] Role-based access control is enforced
- [ ] Authentication and critical operations are logged
