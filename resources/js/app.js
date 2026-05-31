import { createApp } from 'vue';
import CollectivePayments from './components/CollectivePayments.vue';

const root = document.getElementById('collective-payments-app');
if (root) {
    const props = {
        initialSppts: root.dataset.initialSppts ? JSON.parse(root.dataset.initialSppts) : [],
        initialSearch: root.dataset.search || '',
        initialRtFilter: root.dataset.rtFilter || '',
        initialRwFilter: root.dataset.rwFilter || '',
        initialRtOptions: root.dataset.rtOptions ? JSON.parse(root.dataset.rtOptions) : [],
        initialRwOptions: root.dataset.rwOptions ? JSON.parse(root.dataset.rwOptions) : [],
        pagination: root.dataset.pagination ? JSON.parse(root.dataset.pagination) : {},
        batchUrl: root.dataset.batchUrl || '',
        success: root.dataset.success === 'true',
    };

    createApp(CollectivePayments, props).mount(root);
}
