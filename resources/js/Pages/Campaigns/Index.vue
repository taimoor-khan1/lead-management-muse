<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineOptions({ layout: AppLayout });

defineProps({ campaigns: Object });

const pill = (s) => {
    const map = {
        draft: ['#72777d', '#eef0f2'],
        scheduled: ['#3178c6', '#edf5ff'],
        sending: ['#3178c6', '#edf5ff'],
        completed: ['#268866', '#eaf7f1'],
        email: ['#3178c6', '#edf5ff'],
        whatsapp: ['#268866', '#eaf7f1'],
    };
    const [color, bg] = map[s] || map.draft;
    return { color, background: bg };
};

const launch = (id) => {
    if (confirm('Launch this campaign? Messages will be sent to all pending leads.')) {
        router.post(route('campaigns.launch', id));
    }
};

const destroy = (id) => {
    if (confirm('Delete this campaign?')) {
        router.delete(route('campaigns.destroy', id));
    }
};

const fmtDate = (d) => d ? new Date(d.replace(' ', 'T')).toLocaleString() : '—';
</script>

<template>
    <Head title="Campaigns" />

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold" style="color:#181a1c;">Campaigns</h1>
            <p class="text-sm mt-1" style="color:#72777d;">Bulk outreach over email or WhatsApp.</p>
        </div>
        <Link :href="route('campaigns.create')" class="px-4 py-2 rounded-lg text-sm font-semibold text-white" style="background:#f26a2e;">
            + New Campaign
        </Link>
    </div>

    <div class="bg-white rounded-xl border shadow-sm overflow-hidden" style="border-color:#e5e7e9;">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left border-b" style="border-color:#e5e7e9;">
                    <th class="px-5 py-3 font-semibold" style="color:#72777d;">Name</th>
                    <th class="px-5 py-3 font-semibold" style="color:#72777d;">Channel</th>
                    <th class="px-5 py-3 font-semibold" style="color:#72777d;">Status</th>
                    <th class="px-5 py-3 font-semibold text-center" style="color:#72777d;">Leads</th>
                    <th class="px-5 py-3 font-semibold text-center" style="color:#72777d;">Sent</th>
                    <th class="px-5 py-3 font-semibold" style="color:#72777d;">Updated</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="c in campaigns.data" :key="c.id" class="border-b last:border-0" style="border-color:#e5e7e9;">
                    <td class="px-5 py-3 font-semibold" style="color:#181a1c;">{{ c.name }}</td>
                    <td class="px-5 py-3">
                        <span class="text-xs font-semibold px-2 py-1 rounded-full capitalize" :style="pill(c.channel)">{{ c.channel }}</span>
                    </td>
                    <td class="px-5 py-3">
                        <span class="text-xs font-semibold px-2 py-1 rounded-full capitalize" :style="pill(c.status)">{{ c.status }}</span>
                    </td>
                    <td class="px-5 py-3 text-center" style="color:#181a1c;">{{ c.leads_count }}</td>
                    <td class="px-5 py-3 text-center" style="color:#181a1c;">{{ c.sent_leads_count }}</td>
                    <td class="px-5 py-3 whitespace-nowrap" style="color:#72777d;">{{ fmtDate(c.updated_at) }}</td>
                    <td class="px-5 py-3 text-right whitespace-nowrap">
                        <Link :href="route('campaigns.show', c.id)" class="text-sm font-medium mr-4" style="color:#3178c6;">View</Link>
                        <button v-if="c.status === 'draft' || c.status === 'scheduled'" @click="launch(c.id)" class="text-sm font-medium mr-4" style="color:#f26a2e;">Launch</button>
                        <button @click="destroy(c.id)" class="text-sm font-medium" style="color:#c64f4f;">Delete</button>
                    </td>
                </tr>
                <tr v-if="!campaigns.data.length">
                    <td colspan="7" class="px-5 py-10 text-center" style="color:#72777d;">No campaigns yet. Create your first bulk outreach.</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
