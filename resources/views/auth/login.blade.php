@extends('layouts.app')

@section('title', 'Login - Sido')

@section('content')
<div class="min-h-screen flex items-center justify-center p-6">
    <div class="glass-panel w-full max-w-md p-8">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-cyber-light">Welcome Back</h1>
            <p class="text-gray-400 mt-2">Enter your credentials to access the Sido dashboard.</p>
        </div>

        <form method="POST" action="{{ route('login.post') }}" class="space-y-6">
            @csrf

            <div>
                <label for="username" class="block text-gray-400 text-sm mb-2">Username or Email</label>
                <input id="username" name="username" type="text" value="{{ old('username') }}" required autofocus class="input-cyber" placeholder="zolvirm" />
                @error('username')
                    <p class="text-cyber-pink text-sm mt-2">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password" class="block text-gray-400 text-sm mb-2">Password</label>
                <input id="password" name="password" type="password" required class="input-cyber" placeholder="••••••••" />
                @error('password')
                    <p class="text-cyber-pink text-sm mt-2">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="btn-cyber-primary w-full justify-center">
                Login
            </button>

            @if($errors->any())
                <div class="text-cyber-pink text-sm mt-4">
                    {{ $errors->first() }}
                </div>
            @endif
        </form>
    </div>
</div>
@endsection
