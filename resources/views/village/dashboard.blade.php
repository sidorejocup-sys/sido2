@extends('layouts.app')

@section('content')
<div class="p-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-cyber-light mb-2">
            Village Dashboard
        </h1>
        <p class="text-gray-400">{{ auth()->user()->role === 'rt' ? 'RT ' . auth()->user()->rt : (auth()->user()->role === 'kasun_rw' ? 'RW ' . auth()->user()->rw : 'Village') }} Overview</p>
    </div>

    <!-- Scope Info -->
    <div class="mb-8 glass-panel p-6 glow-border-cyan">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-400 uppercase tracking-wider">Current Scope</p>
                <p class="text-2xl font-bold text-cyber-cyan">
                    @if(auth()->user()->role === 'rt')
                        RT {{ auth()->user()->rt }}
                    @elseif(auth()->user()->role === 'kasun_rw')
                        RW {{ auth()->user()->rw }}
                    @else
                        Village-wide
                    @endif
                </p>
            </div>
            <div class="text-right">
                <p class="text-sm text-gray-400 uppercase tracking-wider">Data Updated</p>
                <p class="text-lg font-semibold text-cyber-light">Just now</p>
            </div>
        </div>
    </div>

    <!-- Key Metrics -->
    <div class="grid-responsive mb-8">
        <!-- Total SPPTs -->
        <div class="analytics-card glow-border-cyan">
            <div class="analytics-title">Total SPPTs</div>
            <div class="counter-badge">{{ $totalSppt }}</div>
            <div class="text-xs text-gray-400 mt-3">In your scope</div>
        </div>

        <!-- Paid SPPTs -->
        <div class="analytics-card glow-border-green">
            <div class="analytics-title">Paid (Lunas)</div>
            <div class="counter-badge bg-cyber-green/20 text-cyber-green border-cyber-green/30">{{ $paidSppt }}</div>
            <div class="text-xs text-gray-400 mt-3">{{ round(($paidSppt / max($totalSppt, 1)) * 100, 1) }}% collection</div>
        </div>

        <!-- Outstanding -->
        <div class="analytics-card glow-border-pink">
            <div class="analytics-title">Outstanding (Piutang)</div>
            <div class="counter-badge bg-cyber-pink/20 text-cyber-pink border-cyber-pink/30">{{ $pendingSppt }}</div>
            <div class="text-xs text-gray-400 mt-3">{{ round(($pendingSppt / max($totalSppt, 1)) * 100, 1) }}% pending</div>
        </div>

        <!-- Collection Rate -->
        <div class="analytics-card glow-border-purple">
            <div class="analytics-title">Collection Rate</div>
            <div class="analytics-value text-cyber-violet">{{ round(($paidSppt / max($totalSppt, 1)) * 100, 1) }}%</div>
            <div class="text-xs text-gray-400 mt-3">Target: 85%</div>
        </div>
    </div>

    <!-- Collective Payments Card -->
    <div class="analytics-card glow-border-cyan mb-8 bg-gradient-to-r from-[#0f172a] via-[#111827] to-[#0e0f23]">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h3 class="text-2xl font-bold text-cyber-light">Collective Payments</h3>
                <p class="text-gray-400 mt-2">Process multiple outstanding SPPTs in one flow. Search by taxpayer or filter by RT/RW to settle bills faster.</p>
                <div class="mt-4 grid gap-3 sm:grid-cols-3">
                    <div class="rounded-xl border border-white/10 bg-white/5 p-4">
                        <p class="text-xs uppercase text-gray-400">Outstanding Items</p>
                        <p class="text-2xl font-semibold text-cyber-pink">{{ $pendingSppt }}</p>
                    </div>
                    <div class="rounded-xl border border-white/10 bg-white/5 p-4">
                        <p class="text-xs uppercase text-gray-400">Expected collection</p>
                        <p class="text-2xl font-semibold text-cyber-green">Rp {{ number_format(($statistics['total_pajak_terhutang'] ?? 0) - ($statistics['total_pembayaran'] ?? 0), 0, ',', '.') }}</p>
                    </div>
                    <div class="rounded-xl border border-white/10 bg-white/5 p-4">
                        <p class="text-xs uppercase text-gray-400">Ready for batch</p>
                        <p class="text-2xl font-semibold text-cyber-cyan">Instant setup</p>
                    </div>
                </div>
            </div>
            <a href="{{ route('village.payments') }}" class="btn-cyber-primary w-full lg:w-auto mt-4 lg:mt-0">
                Open Collective Payments
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>
        </div>
    </div>

    <!-- Charts -->
    <div class="grid md:grid-cols-2 gap-6 mb-8">
        <!-- Payment Status -->
        <div class="analytics-card glow-border-purple">
            <h3 class="text-xl font-bold text-cyber-light mb-4">Payment Status</h3>
            <div id="status-chart" style="height: 300px;"></div>
        </div>

        <!-- Statistics -->
        <div class="analytics-card glow-border-cyan">
            <h3 class="text-xl font-bold text-cyber-light mb-4">Quick Statistics</h3>
            <div class="space-y-3">
                <div class="flex justify-between text-gray-300 pb-3 border-b border-white/10">
                    <span>Total Pajak Terhutang</span>
                    <span class="text-cyber-pink font-bold">Rp {{ number_format($statistics['total_pajak_terhutang'] ?? 0, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between text-gray-300 pb-3 border-b border-white/10">
                    <span>Total Pembayaran</span>
                    <span class="text-cyber-green font-bold">Rp {{ number_format($statistics['total_pembayaran'] ?? 0, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between text-gray-300">
                    <span>Remaining Arrears</span>
                    <span class="text-cyber-cyan font-bold">Rp {{ number_format(($statistics['total_pajak_terhutang'] ?? 0) - ($statistics['total_pembayaran'] ?? 0), 0, ',', '.') }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- View Full Details -->
    <div class="glass-panel p-6 glow-border-cyan">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-xl font-bold text-cyber-light">Detailed Reports</h3>
                <p class="text-sm text-gray-400 mt-1">View payments and transactions</p>
            </div>
            <a href="{{ route('village.payments') }}" class="btn-cyber-primary">
                View Payments
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts@latest"></script>
<script>
    const statusChartOptions = {
        series: [{{ $paidSppt }}, {{ $pendingSppt }}],
        chart: {
            type: 'donut',
            toolbar: { show: false },
            background: 'transparent'
        },
        colors: ['#06ffa5', '#ff006e'],
        labels: ['Lunas', 'Piutang'],
        plotOptions: {
            pie: {
                donut: { size: '75%', background: 'transparent' }
            }
        },
        dataLabels: { enabled: false },
        legend: { labels: { colors: '#e0e0e0' } },
        tooltip: { theme: 'dark' }
    };
    new ApexCharts(document.querySelector('#status-chart'), statusChartOptions).render();
</script>
@endpush
@endsection
