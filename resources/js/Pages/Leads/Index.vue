<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, reactive, watch } from 'vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    leads: Object,
    filters: Object,
    stats: Object,
    industries: Array,
    cities: Array,
});

const flash = computed(() => usePage().props.flash || {});

const filters = reactive({
    search: props.filters?.search ?? '',
    industry: props.filters?.industry ?? '',
    city: props.filters?.city ?? '',
    website_status: props.filters?.website_status ?? '',
    stage: props.filters?.stage ?? '',
});

let searchTimer = null;
watch(() => filters.search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(applyFilters, 400);
});

function cleanParams() {
    const out = {};
    for (const [k, v] of Object.entries(filters)) {
        if (v !== '' && v !== null && v !== undefined) out[k] = v;
    }
    return out;
}

function applyFilters() {
    router.get(route('leads.index'), cleanParams(), { preserveState: true, replace: true });
}

function resetFilters() {
    Object.keys(filters).forEach((k) => (filters[k] = ''));
    applyFilters();
}

function syncSheet() {
    router.post(route('leads.sync-sheet'), {}, { preserveScroll: true });
}

const stages = ['new', 'contacted', 'replied', 'proposal', 'won', 'lost'];
const websiteStatuses = ['none', 'weak', 'ok'];

function stagePill(stage) {
    const map = {
        new: 'color:#3178c6;background:#edf5ff;',
        contacted: 'color:#ad6c14;background:#fff6dc;',
        replied: 'color:#268866;background:#eaf7f1;',
        proposal: 'color:#3178c6;background:#edf5ff;',
        won: 'color:#268866;background:#eaf7f1;',
        lost: 'color:#c64f4f;background:#fff0f0;',
    };
    return map[stage] || 'color:#72777d;background:#eef0f2;';
}

function websitePill(status) {
    const map = {
        none: 'color:#c64f4f;background:#fff0f0;',
        weak: 'color:#ad6c14;background:#fff6dc;',
        ok: 'color:#268866;background:#eaf7f1;',
    };
    return map[status] || 'color:#72777d;background:#eef0f2;';
}

const statCards = computed(() => [
    { label: 'Total Leads', value: props.stats?.total ?? 0 },
    { label: 'New', value: props.stats?.new ?? 0 },
    { label: 'Contacted', value: props.stats?.contacted ?? 0 },
    { label: 'Won', value: props.stats?.won ?? 0 },
]);

const inputCls = 'rounded-lg border text-sm px-3 py-2 w-full focus:outline-none focus:ring-2 focus:ring-orange-200';
const inputStyle = 'border-color:#e5e7e9;color:#181a1c;';
const cardStyle = 'background:#fff;border:1px solid #e5e7e9;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,.05);';
</script>

<template>
    <Head title="Leads" />

    <div class="p-6 max-w-[1200px] mx-auto">
        <div class="flex items-center justify-between mb-5 flex-wrap gap-3">
            <h1 class="text-xl font-bold" style="color:#181a1c;">Leads</h1>
            <div class="flex gap-2">
                <button @click="syncSheet" class="px-4 py-2 rounded-lg text-sm font-semibold border"
                    style="border-color:#e5e7e9;color:#181a1c;background:#fff;">Sync Sheet</button>
                <Link :href="route('leads.import')" class="px-4 py-2 rounded-lg text-sm font-semibold border"
                    style="border-color:#e5e7e9;color:#181a1c;background:#fff;">Import</Link>
                <Link :href="route('leads.create')" class="px-4 py-2 rounded-lg text-sm font-semibold text-white"
                    style="background:#f26a2e;">+ Add Lead</Link>
            </div>
        </div>

        <div v-if="flash.message" class="mb-4 px-4 py-3 rounded-lg text-sm"
            style="background:#eaf7f1;color:#268866;border:1px solid #cdeadb;">{{ flash.message }}</div>
        <div v-if="flash.error" class="mb-4 px-4 py-3 rounded-lg text-sm"
            style="background:#fff0f0;color:#c64f4f;border:1px solid #f3d2d2;">{{ flash.error }}</div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-5">
            <div v-for="s in statCards" :key="s.label" class="p-5" :style="cardStyle">
                <div class="text-2xl font-bold" style="color:#181a1c;">{{ s.value }}</div>
                <div class="text-sm" style="color:#72777d;">{{ s.label }}</div>
            </div>
        </div>

        <div class="p-4 mb-4" :style="cardStyle">
            <div class="grid grid-cols-1 md:grid-cols-6 gap-3">
                <input v-model="filters.search" type="text" placeholder="Search name, phone, email…"
                    :class="inputCls" :style="inputStyle" class="md:col-span-2" />
                <select v-model="filters.industry" @change="applyFilters" :class="inputCls" :style="inputStyle">
                    <option value="">All industries</option>
                    <option v-for="i in industries" :key="i" :value="i">{{ i }}</option>
                </select>
                <select v-model="filters.city" @change="applyFilters" :class="inputCls" :style="inputStyle">
                    <option value="">All cities</option>
                    <option v-for="c in cities" :key="c" :value="c">{{ c }}</option>
                </select>
                <select v-model="filters.website_status" @change="applyFilters" :class="inputCls" :style="inputStyle">
                    <option value="">Website: any</option>
                    <option v-for="w in websiteStatuses" :key="w" :value="w" class="capitalize">{{ w }}</option>
                </select>
                <select v-model="filters.stage" @change="applyFilters" :class="inputCls" :style="inputStyle">
                    <option value="">Stage: any</option>
                    <option v-for="s in stages" :key="s" :value="s" class="capitalize">{{ s }}</option>
                </select>
            </div>
            <button @click="resetFilters" class="mt-2 text-xs font-medium" style="color:#f26a2e;">Reset filters</button>
        </div>

        <div class="overflow-x-auto" :style="cardStyle">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left border-b" style="border-color:#e5e7e9;">
                        <th class="px-4 py-3 font-semibold" style="color:#72777d;">Business</th>
                        <th class="px-4 py-3 font-semibold" style="color:#72777d;">Industry</th>
                        <th class="px-4 py-3 font-semibold" style="color:#72777d;">Phone</th>
                        <th class="px-4 py-3 font-semibold" style="color:#72777d;">City</th>
                        <th class="px-4 py-3 font-semibold" style="color:#72777d;">Website Status</th>
                        <th class="px-4 py-3 font-semibold" style="color:#72777d;">Suggested Service</th>
                        <th class="px-4 py-3 font-semibold" style="color:#72777d;">Stage</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="lead in leads.data" :key="lead.id" class="border-b hover:bg-gray-50"
                        style="border-color:#f0f1f2;">
                        <td class="px-4 py-3">
                            <Link :href="route('leads.show', lead.id)" class="font-semibold hover:underline"
                                style="color:#181a1c;">{{ lead.business_name }}</Link>
                            <div v-if="lead.contact_person" class="text-xs" style="color:#72777d;">{{ lead.contact_person }}</div>
                        </td>
                        <td class="px-4 py-3" style="color:#72777d;">{{ lead.industry || '—' }}</td>
                        <td class="px-4 py-3" style="color:#181a1c;">{{ lead.phone || '—' }}</td>
                        <td class="px-4 py-3" style="color:#72777d;">{{ lead.city || '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold capitalize"
                                :style="websitePill(lead.website_status)">{{ lead.website_status }}</span>
                        </td>
                        <td class="px-4 py-3" style="color:#72777d;">{{ lead.suggested_service || '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2.5 py-1 rounded-full text-xs font-semibold capitalize"
                                :style="stagePill(lead.stage)">{{ lead.stage }}</span>
                        </td>
                    </tr>
                    <tr v-if="leads.data.length === 0">
                        <td colspan="7" class="px-4 py-10 text-center" style="color:#72777d;">
                            No leads found. Try adjusting the filters or add a new lead.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="leads.links && leads.links.length > 3" class="flex items-center justify-between mt-4">
            <div class="text-xs" style="color:#72777d;">
                Showing {{ leads.from }}–{{ leads.to }} of {{ leads.total }}
            </div>
            <div class="flex gap-1">
                <template v-for="(link, i) in leads.links" :key="i">
                    <Link v-if="link.url" :href="link.url" preserve-scroll
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold border"
                        :style="link.active
                            ? 'background:#f26a2e;color:#fff;border-color:#f26a2e;'
                            : 'background:#fff;color:#181a1c;border-color:#e5e7e9;'"
                        v-html="link.label" />
                    <span v-else class="px-3 py-1.5 rounded-lg text-xs border opacity-40"
                        style="border-color:#e5e7e9;color:#72777d;" v-html="link.label" />
                </template>
            </div>
        </div>
    </div>
</template>
