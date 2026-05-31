@extends('layouts.app')

@section('content')
<div class="p-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-cyber-light mb-2">
            My Tax Bills
        </h1>
        <p class="text-gray-400">{{ $message ?? 'View and manage your property tax statements' }}</p>
    </div>

    <!-- Info Banner -->
    <div class="mb-8 glass-panel p-6 border-l-4 border-cyber-cyan glow-border-cyan">
        <div class="flex items-start gap-4">
            <svg class="w-6 h-6 text-cyber-cyan flex-shrink-0 mt-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
            </svg>
            <div>
                <h3 class="font-semibold text-cyber-light">Privacy Notice</h3>
                <p class="text-sm text-gray-400 mt-1">
                    You can only view your own property tax statements. To submit a payment, select a bill and click "Submit Payment Proposal".
                </p>
            </div>
        </div>
    </div>

    <!-- SPPTs List -->
    @if($sppts && count($sppts) > 0)
        <div class="space-y-4">
            @foreach($sppts as $sppt)
            <div class="analytics-card glow-border-purple">
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h3 class="text-xl font-bold text-cyber-light">
                            NOP: {{ $sppt->nop }}
                        </h3>
                        <p class="text-sm text-gray-400 mt-1">
                            Tahun: {{ $sppt->tahun }}
                        </p>
                    </div>
                    <span class="badge-status {{ $sppt->status_bayar === 'lunas' ? 'badge-success' : ($sppt->status_bayar === 'proses_pengajuan' ? 'badge-pending' : 'badge-warning') }}">
                        @if($sppt->status_bayar === 'lunas')
                            Lunas
                        @elseif($sppt->status_bayar === 'proses_pengajuan')
                            Pending Approval
                        @elseif($sppt->status_bayar === 'ditolak')
                            Rejected
                        @else
                            Outstanding
                        @endif
                    </span>
                </div>

                <!-- Details Grid -->
                <div class="grid md:grid-cols-3 gap-4 mb-6 pb-6 border-b border-white/10">
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wider">NJOP Bumi</p>
                        <p class="text-lg font-bold text-cyber-green">Rp {{ number_format($sppt->njop_bumi, 0, ',', '.') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wider">NJOP Bangunan</p>
                        <p class="text-lg font-bold text-cyber-cyan">Rp {{ number_format($sppt->njop_bangunan, 0, ',', '.') }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase tracking-wider">Pajak Terhutang</p>
                        <p class="text-lg font-bold text-cyber-pink">Rp {{ number_format($sppt->pajak_terhutang, 0, ',', '.') }}</p>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex gap-3">
                    @if($sppt->status_bayar === 'piutang')
                    <form action="{{ route('user.payment-proposal', $sppt->id_sppt) }}" method="POST" class="flex-1">
                        @csrf
                        <button type="submit" class="btn-cyber-primary w-full justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Submit Payment Proposal
                        </button>
                    </form>
                    @elseif($sppt->status_bayar === 'proses_pengajuan')
                    <div class="flex-1 glass-panel px-4 py-3 rounded-lg flex items-center justify-center text-gray-400">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        Awaiting Admin Approval
                    </div>
                    @elseif($sppt->status_bayar === 'lunas')
                    <div class="flex-1 glass-panel px-4 py-3 rounded-lg flex items-center justify-center text-cyber-green">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        Paid
                    </div>
                    @else
                    <div class="flex-1 glass-panel px-4 py-3 rounded-lg flex items-center justify-center text-cyber-pink">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                        Proposal Rejected
                    </div>
                    @endif
                </div>

                @if($errors->any())
                    <div class="mt-4 p-3 bg-cyber-pink/20 border border-cyber-pink/30 rounded-lg">
                        <p class="text-sm text-cyber-pink">{{ $errors->first() }}</p>
                    </div>
                @endif
            </div>
            @endforeach
        </div>

        <!-- Pagination -->
        @if($sppts instanceof \Illuminate\Pagination\Paginator)
        <div class="pagination-cyber mt-8">
            {{ $sppts->links() }}
        </div>
        @endif
    @else
        <!-- Empty State -->
        <div class="analytics-card glow-border-cyan text-center py-12">
            <svg class="w-16 h-16 text-gray-500 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h3 class="text-xl font-bold text-cyber-light mb-2">No Tax Bills Found</h3>
            <p class="text-gray-400">{{ $message ?? 'Your property data is not yet registered in the system.' }}</p>
            <p class="text-sm text-gray-500 mt-2">Please contact your RT/RW leader for assistance.</p>
        </div>
    @endif
</div>

@push('scripts')
<script>
    // Handle form submission with confirmation
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!confirm('Submit payment proposal? This action requires admin approval.')) {
                e.preventDefault();
            }
        });
    });
</script>
@endpush
@endsection
