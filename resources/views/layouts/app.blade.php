<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sido - Tax Management System')</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://rsms.me/">
    <link rel="stylesheet" href="https://rsms.me/inter/inter.css">
    
    <!-- Styles -->
    @vite(['resources/css/cyberpunk.css', 'resources/js/app.js'])
    
    @stack('styles')
</head>
<body class="bg-cyber-darker">
    <div class="min-h-screen flex">
        <!-- Sidebar Navigation -->
        @auth
        <nav class="sidebar w-64 p-6 hidden lg:block">
            <!-- Logo -->
            <div class="mb-8 pb-8 border-b border-white/10">
                <h1 class="text-2xl font-bold">
                    <span class="text-cyber-violet">Sido</span>
                    <span class="text-xs text-gray-500 block mt-1">Tax Management</span>
                </h1>
            </div>

            <!-- Navigation Links -->
            <div class="space-y-2">
                @if(auth()->user()->role === 'super_admin')
                    <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-3m0 0l7-4 7 4M5 9v10a1 1 0 001 1h12a1 1 0 001-1V9m-9 5h4" />
                        </svg>
                        Dashboard
                    </a>
                    <a href="{{ route('admin.import') }}" class="nav-link">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Import Data
                    </a>
                    <a href="{{ route('admin.export') }}" class="nav-link">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Export Report
                    </a>
                @elseif(in_array(auth()->user()->role, ['kades', 'kasun_rw', 'rt']))
                    <a href="{{ route('village.dashboard') }}" class="nav-link {{ request()->routeIs('village.dashboard') ? 'active' : '' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-3m0 0l7-4 7 4M5 9v10a1 1 0 001 1h12a1 1 0 001-1V9m-9 5h4" />
                        </svg>
                        Dashboard
                    </a>
                    <a href="{{ route('village.payments') }}" class="nav-link">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Payments
                    </a>
                    <a href="{{ route('village.statistics') }}" class="nav-link">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        Statistics
                    </a>
                @elseif(auth()->user()->role === 'pengguna')
                    <a href="{{ route('user.dashboard') }}" class="nav-link {{ request()->routeIs('user.dashboard') ? 'active' : '' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-3m0 0l7-4 7 4M5 9v10a1 1 0 001 1h12a1 1 0 001-1V9m-9 5h4" />
                        </svg>
                        Dashboard
                    </a>
                    <a href="{{ route('user.sppt') }}" class="nav-link {{ request()->routeIs('user.sppt') ? 'active' : '' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        My Bills
                    </a>
                @endif
            </div>

            <!-- User Info -->
            <div class="mt-auto pt-6 border-t border-white/10">
                <div class="glass-panel p-4 mb-4">
                    <p class="text-xs text-gray-400 uppercase tracking-wider">Logged in as</p>
                    <p class="text-sm font-bold text-cyber-light mt-1">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-cyber-violet mt-1 capitalize">{{ auth()->user()->role }}</p>
                </div>

                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn-cyber w-full justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        Logout
                    </button>
                </form>
            </div>
        </nav>
        @endauth

        <!-- Main Content -->
        <main class="flex-1">
            <!-- Top Bar -->
            @auth
            <div class="glass-panel border-b border-white/10 px-8 py-4 sticky top-0 z-40">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-cyber-light">
                        @yield('page-title', 'Dashboard')
                    </h2>
                    <div class="flex items-center gap-4">
                        <!-- Time -->
                        <div class="text-sm text-gray-400">
                            <span id="current-time"></span>
                        </div>

                        <!-- Mobile Menu Toggle -->
                        <button class="lg:hidden glass-panel p-2 rounded-lg text-cyber-violet hover:text-cyber-pink transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
            @endauth

            <!-- Page Content -->
            <div>
                @yield('content')
            </div>
        </main>
    </div>

    <!-- Scripts -->
    @stack('scripts')
    <script>
        // Update time
        function updateTime() {
            const now = new Date();
            document.getElementById('current-time').textContent = now.toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
        }
        updateTime();
        setInterval(updateTime, 1000);

        // CSRF Token for AJAX
        window.csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    </script>
</body>
</html>
