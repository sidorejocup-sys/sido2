@extends('layouts.app')

@section('content')
<div class="p-8">
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-4xl font-bold text-cyber-light">Collective Payment Portal</h1>
            <p class="text-gray-400 mt-2">Search by taxpayer, isolate by RT/RW, and batch-clear outstanding SPPTs.</p>
        </div>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="text-right">
                <p class="text-xs uppercase tracking-wider text-gray-400">Selected bills</p>
                <p id="selected-summary" class="text-2xl font-bold text-cyber-green">0 SPPT</p>
            </div>
            <button id="clear-selection" class="btn-cyber w-full sm:w-auto">Clear selection</button>
        </div>
    </div>

    @if(session('success'))
        <div class="glass-panel mb-6 p-4 border border-cyber-green/30 bg-cyber-green/10 text-cyber-light">
            {{ session('success') }}
        </div>
    @endif
    @if($errors->has('batch') || $errors->has('selected_sppt_ids'))
        <div class="glass-panel mb-6 p-4 border border-cyber-pink/30 bg-cyber-pink/10 text-cyber-light">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="glass-panel p-6 glow-border-cyan mb-6">
        <form method="GET" action="{{ route('village.payments') }}" class="grid gap-4 lg:grid-cols-4">
            <label class="block text-sm text-gray-400">
                Search Subject
                <input type="text" name="search" value="{{ $search }}" placeholder="NIK, Nama, atau NOP" class="input-cyber mt-2" />
            </label>
            <label class="block text-sm text-gray-400">
                Filter RT
                <select name="rt" class="select-cyber mt-2">
                    <option value="">All RT</option>
                    @foreach($rtOptions as $rt)
                        <option value="{{ $rt }}" @selected($rt === $rtFilter)>{{ $rt }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block text-sm text-gray-400">
                Filter RW
                <select name="rw" class="select-cyber mt-2">
                    <option value="">All RW</option>
                    @foreach($rwOptions as $rw)
                        <option value="{{ $rw }}" @selected($rw === $rwFilter)>{{ $rw }}</option>
                    @endforeach
                </select>
            </label>
            <button type="submit" class="btn-cyber-primary w-full h-14 mt-3 lg:mt-0">Apply Filters</button>
        </form>
    </div>

    <div class="glass-panel p-6 overflow-x-auto mb-6">
        <form id="batch-form" action="{{ route('village.payments.batch') }}" method="POST">
            @csrf
            <input type="hidden" name="selected_sppt_ids" id="selected-sppt-ids" value="" />
            <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-sm text-gray-400">Pending SPPT records</p>
                    <p class="text-2xl font-semibold text-cyber-light">{{ $sppts->total() }} piutang</p>
                </div>
                <button type="submit" class="btn-cyber-primary w-full lg:w-auto">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-6a2 2 0 012-2h2a2 2 0 012 2v6m4 0H5" />
                    </svg>
                    Pay Selected Bills
                </button>
            </div>

            <table class="min-w-full text-left text-sm text-gray-100">
                <thead>
                    <tr class="text-xs uppercase tracking-wider text-gray-400 border-b border-white/10">
                        <th class="px-4 py-3 w-12">Select</th>
                        <th class="px-4 py-3">Subject / SPPT</th>
                        <th class="px-4 py-3">Location</th>
                        <th class="px-4 py-3">NOP</th>
                        <th class="px-4 py-3">Year</th>
                        <th class="px-4 py-3">Amount Due</th>
                        <th class="px-4 py-3">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $groupedSppts = $sppts->getCollection()->groupBy(function ($item) {
                            return optional(optional($item->objekPajak)->subjekPajak)->NIK ?? 'unknown';
                        });
                    @endphp

                    @forelse($groupedSppts as $nik => $group)
                        @php
                            $subject = optional(optional($group->first()->objekPajak)->subjekPajak);
                            $rowCount = $group->count();
                        @endphp
                        <tr class="bg-white/5 border-b border-white/10 subject-row" data-subjek-nik="{{ $nik }}">
                            <td class="px-4 py-4 align-top">
                                <input type="checkbox" class="parent-checkbox h-4 w-4 text-cyber-green" data-subjek-nik="{{ $nik }}" />
                            </td>
                            <td class="px-4 py-4" colspan="5">
                                <div class="font-semibold text-cyber-light">{{ $subject->nama ?? 'Unknown Subject' }} <span class="text-xs text-gray-400">({{ $nik }})</span></div>
                                <div class="text-xs text-gray-500">{{ $subject->alamat ?? 'Alamat tidak tersedia' }}</div>
                                <div class="text-xs text-gray-500 mt-1">RT {{ $subject->RT ?? '-' }} / RW {{ $subject->RW ?? '-' }}</div>
                                <div class="text-xs text-cyber-green mt-2">{{ $rowCount }} outstanding SPPT(s)</div>
                            </td>
                        </tr>
                        @foreach($group as $sppt)
                            <tr class="sppt-row border-b border-white/10" data-subjek-nik="{{ $nik }}">
                                <td class="px-4 py-4 align-top">
                                    <input type="checkbox"
                                        class="child-checkbox h-4 w-4 text-cyber-green"
                                        data-sppt-id="{{ $sppt->id_sppt }}"
                                        data-subjek-nik="{{ $nik }}"
                                        data-due="{{ $sppt->pajak_terhutang }}"
                                    />
                                </td>
                                <td class="px-4 py-4">
                                    <div class="font-semibold text-cyber-light">{{ $subject->nama ?? 'Unknown' }}</div>
                                    <div class="text-xs text-gray-500">{{ $subject->NIK ?? 'N/A' }}</div>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="text-gray-300">RT {{ $subject->RT ?? '-' }}</div>
                                    <div class="text-gray-300">RW {{ $subject->RW ?? '-' }}</div>
                                </td>
                                <td class="px-4 py-4 text-cyber-cyan">{{ $sppt->nop }}</td>
                                <td class="px-4 py-4">{{ $sppt->tahun }}</td>
                                <td class="px-4 py-4 text-cyber-pink">Rp {{ number_format($sppt->pajak_terhutang, 0, ',', '.') }}</td>
                                <td class="px-4 py-4 text-yellow-300">{{ ucfirst($sppt->status_bayar) }}</td>
                            </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">Tidak ada SPPT yang cocok dengan filter atau pencarian Anda.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </form>
    </div>

    <div class="flex items-center justify-between text-sm text-gray-400">
        <div>Showing {{ $sppts->firstItem() ?? 0 }} - {{ $sppts->lastItem() ?? 0 }} of {{ $sppts->total() }} records</div>
        <div>{{ $sppts->links() }}</div>
    </div>
</div>

@push('scripts')
<script>
    const STORAGE_KEY = 'collectivePaymentSelectedSppt';

    const selectedSummary = document.getElementById('selected-summary');
    const selectedSpptIdsInput = document.getElementById('selected-sppt-ids');
    const clearSelectionButton = document.getElementById('clear-selection');
    const childCheckboxes = Array.from(document.querySelectorAll('.child-checkbox'));
    const parentCheckboxes = Array.from(document.querySelectorAll('.parent-checkbox'));

    let selectedMap = new Map(JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]').map(item => [String(item.id), item]));

    function persistSelection() {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(Array.from(selectedMap.values())));
        updateSelectionSummary();
    }

    function updateSelectionSummary() {
        const count = selectedMap.size;
        const totalDue = Array.from(selectedMap.values()).reduce((sum, item) => sum + parseFloat(item.due || 0), 0);
        selectedSummary.textContent = `${count} SPPT selected`;
        if (count > 0) {
            selectedSummary.textContent += ` • Rp ${Intl.NumberFormat('id-ID').format(totalDue)}`;
        }
    }

    function toggleSpptSelection(spptId, data) {
        if (data.selected) {
            selectedMap.set(spptId, data);
        } else {
            selectedMap.delete(spptId);
        }
        persistSelection();
    }

    function refreshParentState(nik) {
        const groupChildren = childCheckboxes.filter(cb => cb.dataset.subjekNik === nik);
        const parent = parentCheckboxes.find(cb => cb.dataset.subjekNik === nik);
        if (!parent) {
            return;
        }

        const checkedCount = groupChildren.filter(cb => cb.checked).length;
        parent.checked = checkedCount === groupChildren.length && groupChildren.length > 0;
        parent.indeterminate = checkedCount > 0 && checkedCount < groupChildren.length;
    }

    function restoreSelections() {
        childCheckboxes.forEach(cb => {
            const spptId = String(cb.dataset.spptId);
            if (selectedMap.has(spptId)) {
                cb.checked = true;
            }
        });

        const observedNiks = Array.from(new Set(childCheckboxes.map(cb => cb.dataset.subjekNik)));
        observedNiks.forEach(refreshParentState);
        updateSelectionSummary();
    }

    childCheckboxes.forEach(cb => {
        cb.addEventListener('change', () => {
            const spptId = String(cb.dataset.spptId);
            const rowData = {
                id: spptId,
                due: cb.dataset.due,
                subjek_nik: cb.dataset.subjekNik,
            };

            toggleSpptSelection(spptId, {
                ...rowData,
                selected: cb.checked,
            });
            refreshParentState(cb.dataset.subjekNik);
        });
    });

    parentCheckboxes.forEach(cb => {
        cb.addEventListener('change', () => {
            const nik = cb.dataset.subjekNik;
            const groupChildren = childCheckboxes.filter(child => child.dataset.subjekNik === nik);
            groupChildren.forEach(child => {
                child.checked = cb.checked;
                const spptId = String(child.dataset.spptId);
                toggleSpptSelection(spptId, {
                    id: spptId,
                    due: child.dataset.due,
                    subjek_nik: nik,
                    selected: cb.checked,
                });
            });
        });
    });

    clearSelectionButton.addEventListener('click', event => {
        event.preventDefault();
        selectedMap.clear();
        localStorage.removeItem(STORAGE_KEY);
        childCheckboxes.forEach(cb => cb.checked = false);
        parentCheckboxes.forEach(cb => {
            cb.checked = false;
            cb.indeterminate = false;
        });
        updateSelectionSummary();
    });

    document.getElementById('batch-form').addEventListener('submit', function (event) {
        const selectedIds = Array.from(selectedMap.keys());
        if (selectedIds.length === 0) {
            event.preventDefault();
            alert('Pilih setidaknya satu SPPT untuk pembayaran batch.');
            return;
        }

        const confirmed = confirm(`Bayar ${selectedIds.length} SPPT sekaligus? Tindakan ini tidak dapat dibatalkan.`);
        if (!confirmed) {
            event.preventDefault();
            return;
        }

        selectedSpptIdsInput.value = selectedIds.join(',');
    });

    restoreSelections();

    @if(session('success'))
        localStorage.removeItem(STORAGE_KEY);
    @endif
</script>
@endpush
@endsection