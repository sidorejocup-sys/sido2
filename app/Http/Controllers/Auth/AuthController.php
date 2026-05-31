<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\SecurityTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    use SecurityTrait;

    /**
     * Show the login form.
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle a login request.
     *
     * Rate limited to 5 attempts per minute per IP.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('username', $credentials['username'])
            ->orWhere('email', $credentials['username'])
            ->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            $this->logAuthEvent('login_failed', [
                'username' => $credentials['username'],
            ]);

            return back()->withErrors([
                'username' => 'The provided credentials do not match our records.',
            ]);
        }

        auth()->login($user, $request->boolean('remember'));

        $this->logAuthEvent('login_success', [
            'user_id' => $user->id,
            'username' => $user->username,
        ]);

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Handle a logout request.
     */
    public function logout(Request $request)
    {
        $this->logAuthEvent('logout', [
            'user_id' => auth()->id(),
        ]);

        auth()->logout();

        return redirect(route('login'));
    }
}
