<template>
    <div class="p-8">
        <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-4xl font-bold text-cyber-light">Collective Payment Portal</h1>
                <p class="text-gray-400 mt-2">Search by taxpayer, isolate by RT/RW, and batch-clear outstanding SPPTs.</p>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <div class="text-right">
                    <p class="text-xs uppercase tracking-wider text-gray-400">Selected bills</p>
                    <p class="text-2xl font-bold text-cyber-green">{{ selectedCount }} SPPT</p>
                    <p v-if="selectedCount > 0" class="text-sm text-gray-300">Rp {{ formattedSelectedTotal }}</p>
                </div>
                <button type="button" @click="clearSelection" class="btn-cyber w-full sm:w-auto">Clear selection</button>
            </div>
        </div>

        <div v-if="successMessage" class="glass-panel mb-6 p-4 border border-cyber-green/30 bg-cyber-green/10 text-cyber-light">
            {{ successMessage }}
        </div>
        <div v-if="errors.length" class="glass-panel mb-6 p-4 border border-cyber-pink/30 bg-cyber-pink/10 text-cyber-light">
            <ul class="list-disc list-inside space-y-1">
                <li v-for="error in errors" :key="error">{{ error }}</li>
            </ul>
        </div>

        <div class="glass-panel p-6 glow-border-cyan mb-6">
            <form method="GET" :action="currentRoute" class="grid gap-4 lg:grid-cols-4">
                <label class="block text-sm text-gray-400">
                    Search Subject
                    <input type="text" name="search" :value="search" placeholder="NIK, Nama, atau NOP" class="input-cyber mt-2" />
                </label>
                <label class="block text-sm text-gray-400">
                    Filter RT
                    <select name="rt" class="select-cyber mt-2">
                        <option value="">All RT</option>
                        <option v-for="rt in rtOptions" :key="rt" :value="rt" :selected="rt === rtFilter">{{ rt }}</option>
                    </select>
                </label>
                <label class="block text-sm text-gray-400">
                    Filter RW
                    <select name="rw" class="select-cyber mt-2">
                        <option value="">All RW</option>
                        <option v-for="rw in rwOptions" :key="rw" :value="rw" :selected="rw === rwFilter">{{ rw }}</option>
                    </select>
                </label>
                <button type="submit" class="btn-cyber-primary w-full h-14 mt-3 lg:mt-0">Apply Filters</button>
            </form>
        </div>

        <div class="glass-panel p-6 overflow-x-auto mb-6">
            <form id="batch-form" :action="batchUrl" method="POST" @submit.prevent="submitBatch">
                <input type="hidden" name="selected_sppt_ids" :value="selectedIdsString" />
                <input type="hidden" name="_token" :value="csrfToken" />
                <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <p class="text-sm text-gray-400">Pending SPPT records</p>
                        <p class="text-2xl font-semibold text-cyber-light">{{ totalSppts }} piutang</p>
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
                        <template v-if="grouped.length">
                            <template v-for="group in grouped" :key="group.nik">
                                <tr class="bg-white/5 border-b border-white/10" :data-subjek-nik="group.nik">
                                    <td class="px-4 py-4 align-top">
                                        <input
                                            type="checkbox"
                                            class="h-4 w-4 text-cyber-green"
                                            :checked="group.selectedAll"
                                            :indeterminate.prop="group.indeterminate"
                                            @change="toggleSubject(group.nik, $event.target.checked)"
                                        />
                                    </td>
                                    <td class="px-4 py-4" colspan="5">
                                        <div class="font-semibold text-cyber-light">{{ group.subject.nama || 'Unknown Subject' }} <span class="text-xs text-gray-400">({{ group.nik }})</span></div>
                                        <div class="text-xs text-gray-500">{{ group.subject.alamat || 'Alamat tidak tersedia' }}</div>
                                        <div class="text-xs text-gray-500 mt-1">RT {{ group.subject.RT || '-' }} / RW {{ group.subject.RW || '-' }}</div>
                                        <div class="text-xs text-cyber-green mt-2">{{ group.sppts.length }} outstanding SPPT(s)</div>
                                    </td>
                                </tr>
                                <tr v-for="sppt in group.sppts" :key="sppt.id_sppt" class="sppt-row border-b border-white/10">
                                    <td class="px-4 py-4 align-top">
                                        <input
                                            type="checkbox"
                                            class="h-4 w-4 text-cyber-green"
                                            :checked="isSelected(sppt.id_sppt)"
                                            @change="toggleSppt(sppt, $event.target.checked)"
                                        />
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="font-semibold text-cyber-light">{{ group.subject.nama || 'Unknown' }}</div>
                                        <div class="text-xs text-gray-500">{{ group.subject.NIK || 'N/A' }}</div>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div class="text-gray-300">RT {{ group.subject.RT || '-' }}</div>
                                        <div class="text-gray-300">RW {{ group.subject.RW || '-' }}</div>
                                    </td>
                                    <td class="px-4 py-4 text-cyber-cyan">{{ sppt.nop }}</td>
                                    <td class="px-4 py-4">{{ sppt.tahun }}</td>
                                    <td class="px-4 py-4 text-cyber-pink">Rp {{ formatCurrency(sppt.pajak_terhutang) }}</td>
                                    <td class="px-4 py-4 text-yellow-300">{{ capitalize(sppt.status_bayar) }}</td>
                                </tr>
                            </template>
                        </template>
                        <tr v-else>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">Tidak ada SPPT yang cocok dengan filter atau pencarian Anda.</td>
                        </tr>
                    </tbody>
                </table>
            </form>
        </div>

        <div class="flex items-center justify-between text-sm text-gray-400">
            <div>Showing {{ pagination.firstItem }} - {{ pagination.lastItem }} of {{ pagination.total }} records</div>
            <div v-html="pagination.links"></div>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';

const props = defineProps({
    initialSppts: {
        type: Array,
        default: () => [],
    },
    initialSearch: {
        type: String,
        default: '',
    },
    initialRtFilter: {
        type: String,
        default: '',
    },
    initialRwFilter: {
        type: String,
        default: '',
    },
    initialRtOptions: {
        type: Array,
        default: () => [],
    },
    initialRwOptions: {
        type: Array,
        default: () => [],
    },
    batchUrl: {
        type: String,
        default: '',
    },
    pagination: {
        type: Object,
        default: () => ({
            firstItem: 0,
            lastItem: 0,
            total: 0,
            links: '',
        }),
    },
    success: {
        type: Boolean,
        default: false,
    },
});

const STORAGE_KEY = 'collectivePaymentSelectedSppt';
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

const search = ref(props.initialSearch);
const rtFilter = ref(props.initialRtFilter);
const rwFilter = ref(props.initialRwFilter);
const rtOptions = ref(props.initialRtOptions);
const rwOptions = ref(props.initialRwOptions);
const currentRoute = window.location.pathname;
const batchUrl = props.batchUrl;
const successMessage = ref(props.success ? 'Batch payment completed successfully.' : '');

const sppts = ref(props.initialSppts);
const selectedMap = reactive({});
const errors = ref([]);

function saveSelection() {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(Object.values(selectedMap)));
}

function loadSelection() {
    try {
        const stored = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]');
        if (Array.isArray(stored)) {
            stored.forEach(item => {
                if (item && item.id) {
                    selectedMap[item.id] = item;
                }
            });
        }
    } catch (e) {
        localStorage.removeItem(STORAGE_KEY);
    }
}

function isSelected(id) {
    return !!selectedMap[String(id)];
}

function toggleSppt(sppt, selected) {
    const id = String(sppt.id_sppt);
    if (selected) {
        selectedMap[id] = {
            id,
            due: Number(sppt.pajak_terhutang) || 0,
            subjek_nik: sppt.objekPajak?.subjekPajak?.NIK || '',
        };
    } else {
        delete selectedMap[id];
    }
    saveSelection();
}

function groupedSppts() {
    const groups = [];
    const map = {};
    sppts.value.forEach(sppt => {
        const nik = sppt.objekPajak?.subjekPajak?.NIK || 'unknown';
        if (!map[nik]) {
            map[nik] = {
                nik,
                subject: sppt.objekPajak?.subjekPajak || {},
                sppts: [],
            };
        }
        map[nik].sppts.push(sppt);
    });
    Object.keys(map).forEach(nik => {
        const group = map[nik];
        const checkedCount = group.sppts.filter(sppt => isSelected(sppt.id_sppt)).length;
        group.selectedAll = checkedCount === group.sppts.length && group.sppts.length > 0;
        group.indeterminate = checkedCount > 0 && checkedCount < group.sppts.length;
        groups.push(group);
    });
    return groups;
}

const grouped = computed(() => groupedSppts());
const selectedCount = computed(() => Object.keys(selectedMap).length);
const selectedTotal = computed(() => Object.values(selectedMap).reduce((sum, item) => sum + Number(item.due || 0), 0));
const selectedIdsString = computed(() => Object.keys(selectedMap).join(','));
const formattedSelectedTotal = computed(() => new Intl.NumberFormat('id-ID').format(selectedTotal.value));
const totalSppts = computed(() => sppts.value.length);

const pagination = reactive({
    firstItem: props.pagination.firstItem || 0,
    lastItem: props.pagination.lastItem || 0,
    total: props.pagination.total || 0,
    links: props.pagination.links || '',
});

function formatCurrency(value) {
    return new Intl.NumberFormat('id-ID').format(Number(value || 0));
}

function capitalize(value) {
    if (!value) return '';
    return value.charAt(0).toUpperCase() + value.slice(1);
}

function toggleSubject(nik, selected) {
    grouped.value.forEach(group => {
        if (group.nik === nik) {
            group.sppts.forEach(sppt => {
                toggleSppt(sppt, selected);
            });
        }
    });
}

function clearSelection() {
    Object.keys(selectedMap).forEach(key => delete selectedMap[key]);
    saveSelection();
}

function submitBatch() {
    errors.value = [];

    if (selectedCount.value === 0) {
        errors.value.push('Pilih setidaknya satu SPPT untuk pembayaran batch.');
        return;
    }

    if (!confirm(`Bayar ${selectedCount.value} SPPT sekaligus? Tindakan ini tidak dapat dibatalkan.`)) {
        return;
    }

    const form = document.createElement('form');
    form.action = batchUrl;
    form.method = 'POST';
    form.style.display = 'none';

    const tokenInput = document.createElement('input');
    tokenInput.name = '_token';
    tokenInput.value = csrfToken;
    form.appendChild(tokenInput);

    const idsInput = document.createElement('input');
    idsInput.name = 'selected_sppt_ids';
    idsInput.value = selectedIdsString.value;
    form.appendChild(idsInput);

    document.body.appendChild(form);
    form.submit();
}

onMounted(() => {
    loadSelection();
    if (props.success) {
        clearSelection();
    }
});
</script>

<style scoped>
    .indeterminate-checkbox::before {
        content: '';
        position: absolute;
        width: 10px;
        height: 2px;
        background: currentColor;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
    }
</style>