@extends('layouts.app')

@section('content')
<div class="p-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-cyber-light mb-2">
            Admin Dashboard
        </h1>
        <p class="text-gray-400">Real-time system overview and analytics</p>
    </div>

    <!-- Global Filters -->
    <div class="mb-8 glass-panel p-6 glow-border-purple">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Search Bar -->
            <div class="md:col-span-2">
                <div class="search-bar">
                    <svg class="w-5 h-5 text-cyber-cyan" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input 
                        type="text" 
                        id="global-search" 
                        placeholder="Search by NIK, Nama, or NOP..."
                        class="input-cyber"
                    >
                </div>
                <div id="search-results" class="mt-4 hidden"></div>
            </div>

            <!-- RT Filter -->
            <select class="select-cyber" id="filter-rt">
                <option value="">Filter by RT</option>
                <option value="001">RT 001</option>
                <option value="002">RT 002</option>
                <option value="003">RT 003</option>
            </select>

            <!-- RW Filter -->
            <select class="select-cyber" id="filter-rw">
                <option value="">Filter by RW</option>
                <option value="01">RW 01</option>
                <option value="02">RW 02</option>
                <option value="03">RW 03</option>
            </select>
        </div>
    </div>

    <!-- Key Metrics -->
    <div class="grid-responsive mb-8">
        <!-- Total Revenue -->
        <div class="analytics-card glow-border-green">
            <div class="analytics-title">Total Revenue Collected</div>
            <div class="analytics-value text-cyber-green">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</div>
            <div class="analytics-change positive">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414-1.414L13.586 7H12z" clip-rule="evenodd" />
                </svg>
                12.5% vs last month
            </div>
        </div>

        <!-- Outstanding Arrears -->
        <div class="analytics-card glow-border-pink">
            <div class="analytics-title">Outstanding Arrears</div>
            <div class="analytics-value text-cyber-pink">Rp {{ number_format($pendingPayments * 5000000, 0, ',', '.') }}</div>
            <div class="analytics-change negative">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M12 13a1 1 0 110 2H7a1 1 0 110-2h5zm0-6a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414-1.414L13.586 7H12z" clip-rule="evenodd" />
                </svg>
                {{ $pendingPayments }} active disputes
            </div>
        </div>

        <!-- Total Subjek Pajak -->
        <div class="analytics-card glow-border-cyan">
            <div class="analytics-title">Total Taxpayers</div>
            <div class="counter-badge">{{ $totalSubjekPajak ?? 0 }}</div>
            <div class="text-xs text-gray-400 mt-3">Active in system</div>
        </div>

        <!-- Pending Approvals -->
        <div class="analytics-card glow-border-purple">
            <div class="analytics-title">Pending Approvals</div>
            <div class="counter-badge bg-cyber-pink/20 text-cyber-pink border-cyber-pink/30">{{ $approvalPending }}</div>
            <div class="text-xs text-gray-400 mt-3">Awaiting review</div>
        </div>
    </div>

    <!-- Revenue vs Arrears Chart -->
    <div class="grid md:grid-cols-2 gap-6 mb-8">
        <!-- Revenue Trend -->
        <div class="analytics-card glow-border-green">
            <h3 class="text-xl font-bold text-cyber-light mb-4">Revenue Trend (12 Months)</h3>
            <div id="revenue-chart" style="height: 300px;"></div>
        </div>

        <!-- Arrears Distribution -->
        <div class="analytics-card glow-border-pink">
            <h3 class="text-xl font-bold text-cyber-light mb-4">Payment Status Distribution</h3>
            <div id="status-chart" style="height: 300px;"></div>
        </div>
    </div>

    <!-- Summary Section -->
    <div class="grid-responsive">
        <div class="analytics-card glow-border-cyan col-span-1 md:col-span-2">
            <h3 class="text-xl font-bold text-cyber-light mb-4">
                Active SPPTs: {{ $totalSppt }}
            </h3>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between text-gray-300">
                    <span>Lunas (Paid)</span>
                    <span class="text-cyber-green">{{ $totalSppt - $pendingPayments }} ({{ round(((($totalSppt - $pendingPayments) / $totalSppt) * 100) ?? 0, 1) }}%)</span>
                </div>
                <div class="flex justify-between text-gray-300">
                    <span>Piutang (Outstanding)</span>
                    <span class="text-cyber-pink">{{ $pendingPayments }} ({{ round((($pendingPayments / $totalSppt) * 100) ?? 0, 1) }}%)</span>
                </div>
                <div class="flex justify-between text-gray-300">
                    <span>Proses Pengajuan (Pending)</span>
                    <span class="text-cyber-yellow">{{ $approvalPending }}</span>
                </div>
            </div>
        </div>

        <div class="analytics-card glow-border-purple">
            <h3 class="text-lg font-bold text-cyber-light mb-4">Quick Actions</h3>
            <div class="space-y-2">
                <form action="{{ route('admin.import') }}" method="POST" enctype="multipart/form-data" class="space-y-2">
                    @csrf
                    <label class="block text-sm text-gray-400">Upload file</label>
                    <input type="file" name="file" class="input-cyber" accept=".csv,.xlsx" required>
                    <button type="submit" class="btn-cyber-primary w-full justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Import Data
                    </button>
                </form>
                <form action="{{ route('admin.export') }}" method="GET">
                    <button type="submit" class="btn-cyber w-full justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Export Report
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts@latest"></script>
<script>
    // Revenue Trend Chart
    const revenueChartOptions = {
        series: [{
            name: 'Revenue',
            data: [120, 135, 142, 155, 168, 175, 180, 192, 205, 215, 225, 240]
        }],
        chart: {
            type: 'line',
            toolbar: { show: false },
            background: 'transparent',
            sparkline: { enabled: false }
        },
        colors: ['#06ffa5'],
        stroke: { curve: 'smooth', width: 3 },
        grid: { borderColor: '#ffffff1a' },
        xaxis: {
            categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            labels: { style: { colors: '#9ca3af' } }
        },
        yaxis: { labels: { style: { colors: '#9ca3af' } } },
        tooltip: {
            theme: 'dark',
            style: { backgroundColor: '#0a0a14', borderColor: '#9d4edd' }
        }
    };
    new ApexCharts(document.querySelector('#revenue-chart'), revenueChartOptions).render();

    // Payment Status Distribution
    const statusChartOptions = {
        series: [
            {{ $totalSppt - $pendingPayments }},
            {{ $pendingPayments }},
            {{ $approvalPending }}
        ],
        chart: {
            type: 'donut',
            toolbar: { show: false },
            background: 'transparent'
        },
        colors: ['#06ffa5', '#ff006e', '#c77dff'],
        labels: ['Lunas', 'Piutang', 'Proses'],
        plotOptions: {
            pie: {
                donut: {
                    size: '75%',
                    background: 'transparent'
                }
            }
        },
        dataLabels: { enabled: false },
        legend: { labels: { colors: '#e0e0e0' } },
        tooltip: { theme: 'dark' }
    };
    new ApexCharts(document.querySelector('#status-chart'), statusChartOptions).render();

    // Global Search
    document.getElementById('global-search').addEventListener('keyup', function(e) {
        const query = e.target.value;
        if (query.length < 3) {
            document.getElementById('search-results').classList.add('hidden');
            return;
        }

        fetch(`/api/search?q=${encodeURIComponent(query)}`)
            .then(res => res.json())
            .then(data => {
                const resultsHtml = data.results.map(r => `
                    <div class="glass-panel p-3 rounded mb-2 text-sm cursor-pointer hover:bg-cyber-purple/20">
                        <strong>${r.name || r.nik || r.nop}</strong> - ${r.type}
                    </div>
                `).join('');
                document.getElementById('search-results').innerHTML = resultsHtml || '<p class="text-gray-500">No results</p>';
                document.getElementById('search-results').classList.remove('hidden');
            });
    });
</script>
@endpush
@endsection
