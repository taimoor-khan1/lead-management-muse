<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineOptions({ layout: AppLayout });

const props = defineProps({ campaign: Object, rows: Array, stats: Object });

const pill = (s) => {
    const map = {
        queued: ['#ad6c14', '#fff6dc'],
        scheduled: ['#3178c6', '#edf5ff'],
        sending: ['#3178c6', '#edf5ff'],
        sent: ['#268866', '#eaf7f1'],
        failed: ['#c64f4f', '#fff0f0'],
        opened: ['#3178c6', '#edf5ff'],
        clicked: ['#268866', '#eaf7f1'],
        replied: ['#268866', '#eaf7f1'],
        draft: ['#72777d', '#eef0f2'],
        pending: ['#ad6c14', '#fff6dc'],
        completed: ['#268866', '#eaf7f1'],
    };
    const [color, bg] = map[s] || map.draft;
    return { color, background: bg };
};

const launch = () => {
    if (confirm('Launch this campaign? Messages will be sent to all pending leads.')) {
        router.post(route('campaigns.launch', props.campaign.id));
    }
};

const cards = [
    { label: 'Total leads', value: props.stats.total },
    { label: 'Sent', value: props.stats.sent },
    { label: 'Failed', value: props.stats.failed },
    { label: 'Opens', value: props.stats.opens },
    { label: 'Clicks', value: props.stats.clicks },
];

const fmtDate = (d) => d ? new Date(d.replace(' ', 'T')).toLocaleString() : '—';
</script>

<template>
    <Head :title="campaign.name" />

    <div class="flex items-start justify-between mb-6">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold" style="color:#181a1c;">{{ campaign.name }}</h1>
                <span class="text-xs font-semibold px-2 py-1 rounded-full capitalize" :style="pill(campaign.status)">{{ campaign.status }}</span>
                <span class="text-xs font-semibold px-2 py-1 rounded-full capitalize" :style="pill(campaign.channel)">{{ campaign.channel }}</span>
            </div>
            <p class="text-sm mt-1" style="color:#72777d;">
                {{ campaign.channel === 'email' ? 'Template: ' + (campaign.template?.name ?? '—') : 'WhatsApp bulk message' }}
            </p>
        </div>
        <div class="flex gap-2">
            <button v-if="campaign.status === 'draft' || campaign.status === 'scheduled'" @click="launch"
                class="px-4 py-2 rounded-lg text-sm font-semibold text-white" style="background:#f26a2e;">
                Launch
            </button>
            <Link :href="route('campaigns.index')" class="px-4 py-2 rounded-lg text-sm font-semibold border" style="border-color:#e5e7e9;color:#181a1c;">
                Back
            </Link>
        </div>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-5 gap-4 mb-6">
        <div v-for="c in cards" :key="c.label" class="bg-white rounded-xl border shadow-sm p-4" style="border-color:#e5e7e9;">
            <div class="text-2xl font-bold" style="color:#181a1c;">{{ c.value }}</div>
            <div class="text-xs mt-1" style="color:#72777d;">{{ c.label }}</div>
        </div>
    </div>

    <div class="bg-white rounded-xl border shadow-sm overflow-hidden" style="border-color:#e5e7e9;">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left border-b" style="border-color:#e5e7e9;">
                    <th class="px-5 py-3 font-semibold" style="color:#72777d;">Lead</th>
                    <th class="px-5 py-3 font-semibold" style="color:#72777d;">City</th>
                    <th class="px-5 py-3 font-semibold" style="color:#72777d;">Status</th>
                    <th class="px-5 py-3 font-semibold" style="color:#72777d;">Sent at</th>
                    <th class="px-5 py-3 font-semibold" style="color:#72777d;">Error</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="r in rows" :key="r.id" class="border-b last:border-0" style="border-color:#e5e7e9;">
                    <td class="px-5 py-3">
                        <div class="font-semibold" style="color:#181a1c;">{{ r.business_name }}</div>
                        <div class="text-xs" style="color:#72777d;">{{ r.contact_person || r.email || '' }}</div>
                    </td>
                    <td class="px-5 py-3" style="color:#72777d;">{{ r.city ?? '—' }}</td>
                    <td class="px-5 py-3">
                        <span class="text-xs font-semibold px-2 py-1 rounded-full" :style="pill(r.status)">{{ r.status }}</span>
                    </td>
                    <td class="px-5 py-3 whitespace-nowrap" style="color:#72777d;">{{ fmtDate(r.sent_at) }}</td>
                    <td class="px-5 py-3 text-xs max-w-[240px] truncate" style="color:#c64f4f;" :title="r.error ?? ''">{{ r.error ?? '—' }}</td>
                </tr>
                <tr v-if="!rows.length">
                    <td colspan="5" class="px-5 py-10 text-center" style="color:#72777d;">No leads in this campaign yet.</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
