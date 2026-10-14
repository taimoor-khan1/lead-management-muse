<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    proposals: Object,
    filters: Object,
    statuses: Array,
});

const search = ref(props.filters?.search || '');
const status = ref(props.filters?.status || '');
const copiedId = ref(null);

const pill = (s) => {
    const map = {
        draft: 'background:#eef0f2; color:#72777d;',
        sent: 'background:#edf5ff; color:#3178c6;',
        accepted: 'background:#eaf7f1; color:#268866;',
        declined: 'background:#fff0f0; color:#c64f4f;',
    };
    return map[s] || map.draft;
};

const pkr = (n) => 'PKR ' + Number(n || 0).toLocaleString('en-PK');

function applyFilters() {
    router.get(route('proposals.index'), { search: search.value, status: status.value }, {
        preserveState: true,
        replace: true,
    });
}

function copyLink(p) {
    const done = () => {
        copiedId.value = p.id;
        setTimeout(() => { copiedId.value = null; }, 1500);
    };
    if (navigator.clipboard?.writeText) {
        navigator.clipboard.writeText(p.public_url).then(done).catch(done);
    } else {
        const el = document.createElement('textarea');
        el.value = p.public_url;
        document.body.appendChild(el);
        el.select();
        document.execCommand('copy');
        document.body.removeChild(el);
        done();
    }
}

function sendProposal(id) {
    if (confirm('Send this proposal?')) {
        router.post(route('proposals.send', id));
    }
}
</script>

<template>
    <Head title="Proposals" />

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold" style="color:#181a1c;">Proposals</h1>
        <Link :href="route('proposals.create')"
            class="px-4 py-2 rounded-lg text-sm font-semibold text-white"
            style="background:#f26a2e;">New Proposal</Link>
    </div>

    <div class="bg-white rounded-xl p-4 mb-4 flex flex-wrap gap-3 items-center" style="box-shadow:0 1px 4px rgba(0,0,0,.05);">
        <input v-model="search" @keyup.enter="applyFilters" type="text" placeholder="Search title or business…"
            class="px-3 py-2 rounded-lg border text-sm w-64" style="border-color:#e5e7e9;" />
        <select v-model="status" @change="applyFilters" class="px-3 py-2 rounded-lg border text-sm" style="border-color:#e5e7e9;">
            <option value="">All statuses</option>
            <option v-for="s in statuses" :key="s" :value="s">{{ s }}</option>
        </select>
        <button @click="applyFilters" class="px-4 py-2 rounded-lg text-sm font-semibold text-white" style="background:#f26a2e;">Filter</button>
    </div>

    <div class="bg-white rounded-xl overflow-hidden" style="box-shadow:0 1px 4px rgba(0,0,0,.05);">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left" style="color:#72777d; border-bottom:1px solid #e5e7e9;">
                    <th class="px-4 py-3 font-medium">Title</th>
                    <th class="px-4 py-3 font-medium">Business</th>
                    <th class="px-4 py-3 font-medium text-right">Total</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3 font-medium">Valid until</th>
                    <th class="px-4 py-3 font-medium text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="p in proposals.data" :key="p.id" style="border-bottom:1px solid #eef0f2;">
                    <td class="px-4 py-3 font-semibold" style="color:#181a1c;">
                        <Link :href="route('proposals.show', p.id)" class="hover:underline">{{ p.title }}</Link>
                    </td>
                    <td class="px-4 py-3" style="color:#72777d;">{{ p.lead?.business_name || '—' }}</td>
                    <td class="px-4 py-3 text-right font-semibold">{{ pkr(p.total) }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold capitalize" :style="pill(p.status)">{{ p.status }}</span>
                    </td>
                    <td class="px-4 py-3" style="color:#72777d;">{{ p.valid_until || '—' }}</td>
                    <td class="px-4 py-3">
                        <div class="flex justify-end gap-2">
                            <button @click="copyLink(p)" class="px-3 py-1.5 rounded-lg text-xs font-semibold border"
                                style="border-color:#e5e7e9; color:#72777d;">
                                {{ copiedId === p.id ? 'Copied!' : 'Copy link' }}
                            </button>
                            <button v-if="p.status === 'draft'" @click="sendProposal(p.id)"
                                class="px-3 py-1.5 rounded-lg text-xs font-semibold text-white" style="background:#f26a2e;">Send</button>
                        </div>
                    </td>
                </tr>
                <tr v-if="!proposals.data.length">
                    <td colspan="6" class="px-4 py-10 text-center" style="color:#72777d;">No proposals yet. Create your first one.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div v-if="proposals.links?.length > 3" class="flex justify-center gap-1 mt-4">
        <template v-for="(link, i) in proposals.links" :key="i">
            <Link v-if="link.url" :href="link.url" v-html="link.label"
                class="px-3 py-1.5 rounded-lg text-sm border"
                :style="link.active ? 'background:#f26a2e; color:#fff; border-color:#f26a2e;' : 'border-color:#e5e7e9; color:#72777d; background:#fff;'" />
            <span v-else v-html="link.label" class="px-3 py-1.5 text-sm" style="color:#c9cdd1;"></span>
        </template>
    </div>
</template>
