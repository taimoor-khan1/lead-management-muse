<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';

defineOptions({ layout: AppLayout });

const props = defineProps({
    kpis: { type: Object, required: true },
    pipeline_summary: { type: Object, required: true },
    recent_leads: { type: Array, default: () => [] },
    upcoming_tasks: { type: Array, default: () => [] },
    recent_sends: { type: Array, default: () => [] },
});

const stageLabels = {
    new: 'New',
    contacted: 'Contacted',
    replied: 'Replied',
    proposal: 'Proposal',
    won: 'Won',
    lost: 'Lost',
};

const stageColors = {
    new: { bg: '#edf5ff', fg: '#3178c6' },
    contacted: { bg: '#fff6dc', fg: '#ad6c14' },
    replied: { bg: '#edf5ff', fg: '#3178c6' },
    proposal: { bg: '#fff6dc', fg: '#ad6c14' },
    won: { bg: '#eaf7f1', fg: '#268866' },
    lost: { bg: '#fff0f0', fg: '#c64f4f' },
};

const statusColors = {
    queued: { bg: '#edf5ff', fg: '#3178c6' },
    sending: { bg: '#fff6dc', fg: '#ad6c14' },
    sent: { bg: '#eaf7f1', fg: '#268866' },
    opened: { bg: '#eaf7f1', fg: '#268866' },
    clicked: { bg: '#eaf7f1', fg: '#268866' },
    replied: { bg: '#eaf7f1', fg: '#268866' },
    failed: { bg: '#fff0f0', fg: '#c64f4f' },
};

const pill = (map, key) => map[key] || { bg: '#f1f2f4', fg: '#72777d' };

const kpiCards = [
    { label: 'Total Leads', value: props.kpis.total_leads ?? 0 },
    { label: 'Contacted', value: props.kpis.contacted ?? 0 },
    { label: 'Replied', value: props.kpis.replied ?? 0 },
    { label: 'Won', value: props.kpis.won ?? 0 },
];

const fmtRevenue = (v) =>
    'PKR ' + Number(v || 0).toLocaleString('en-PK', { maximumFractionDigits: 0 });

const fmtDate = (d) => {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('en-PK', { day: 'numeric', month: 'short', year: 'numeric' });
};

const fmtDateTime = (d) => {
    if (!d) return '—';
    return new Date(d).toLocaleString('en-PK', { day: 'numeric', month: 'short', hour: 'numeric', minute: '2-digit' });
};
</script>

<template>
    <Head title="Dashboard" />

    <div class="mb-6">
        <h1 class="text-2xl font-bold" style="color:#181a1c;">Dashboard</h1>
        <p class="text-sm mt-1" style="color:#72777d;">Your sales pipeline at a glance</p>
    </div>

    <!-- KPI cards -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        <div v-for="card in kpiCards" :key="card.label"
            class="bg-white rounded-xl p-5" style="box-shadow:0 1px 3px rgba(16,24,40,.08);">
            <div class="text-sm mb-2" style="color:#72777d;">{{ card.label }}</div>
            <div class="text-3xl font-bold" style="color:#181a1c;">{{ card.value }}</div>
        </div>
        <div class="bg-white rounded-xl p-5 col-span-2 lg:col-span-1" style="box-shadow:0 1px 3px rgba(16,24,40,.08);">
            <div class="text-sm mb-2" style="color:#72777d;">Revenue Won</div>
            <div class="text-3xl font-bold" style="color:#268866;">{{ fmtRevenue(kpis.revenue_pkr) }}</div>
        </div>
    </div>

    <!-- Pipeline summary strip -->
    <div class="bg-white rounded-xl p-5 mb-6" style="box-shadow:0 1px 3px rgba(16,24,40,.08);">
        <div class="text-sm font-semibold mb-4" style="color:#181a1c;">Pipeline</div>
        <div class="grid grid-cols-3 sm:grid-cols-6 gap-3">
            <div v-for="(key) in Object.keys(stageLabels)" :key="key"
                class="rounded-xl px-3 py-4 text-center" :style="`background:${pill(stageColors, key).bg};`">
                <div class="text-2xl font-bold" :style="`color:${pill(stageColors, key).fg};`">
                    {{ pipeline_summary[key] ?? 0 }}
                </div>
                <div class="text-xs font-medium mt-1" :style="`color:${pill(stageColors, key).fg};`">
                    {{ stageLabels[key] }}
                </div>
            </div>
        </div>
    </div>

    <!-- Two column -->
    <div class="grid lg:grid-cols-3 gap-6">
        <!-- Recent leads -->
        <div class="bg-white rounded-xl p-5 lg:col-span-2" style="box-shadow:0 1px 3px rgba(16,24,40,.08);">
            <div class="text-sm font-semibold mb-4" style="color:#181a1c;">Recent Leads</div>
            <div v-if="recent_leads.length === 0" class="text-sm py-6 text-center" style="color:#72777d;">
                No leads yet.
            </div>
            <div v-else class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide" style="color:#72777d;">
                            <th class="pb-3 pr-4 font-medium">Business</th>
                            <th class="pb-3 pr-4 font-medium">Industry</th>
                            <th class="pb-3 pr-4 font-medium">City</th>
                            <th class="pb-3 pr-4 font-medium">Stage</th>
                            <th class="pb-3 font-medium">Added</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="lead in recent_leads" :key="lead.id" class="border-t" style="border-color:#eef0f2;">
                            <td class="py-3 pr-4 font-semibold" style="color:#181a1c;">{{ lead.business_name }}</td>
                            <td class="py-3 pr-4" style="color:#72777d;">{{ lead.industry || '—' }}</td>
                            <td class="py-3 pr-4" style="color:#72777d;">{{ lead.city || '—' }}</td>
                            <td class="py-3 pr-4">
                                <span class="text-xs font-semibold px-2.5 py-1 rounded-full"
                                    :style="`background:${pill(stageColors, lead.stage).bg}; color:${pill(stageColors, lead.stage).fg};`">
                                    {{ stageLabels[lead.stage] || lead.stage }}
                                </span>
                            </td>
                            <td class="py-3 text-xs" style="color:#72777d;">{{ fmtDate(lead.created_at) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Right column: tasks + emails -->
        <div class="space-y-6">
            <div class="bg-white rounded-xl p-5" style="box-shadow:0 1px 3px rgba(16,24,40,.08);">
                <div class="text-sm font-semibold mb-4" style="color:#181a1c;">Upcoming Tasks</div>
                <div v-if="upcoming_tasks.length === 0" class="text-sm py-4 text-center" style="color:#72777d;">
                    No pending tasks.
                </div>
                <ul v-else class="space-y-3">
                    <li v-for="task in upcoming_tasks" :key="task.id" class="flex items-start gap-3">
                        <span class="mt-0.5 w-4 h-4 rounded-full border-2 shrink-0" style="border-color:#f26a2e;"></span>
                        <div class="min-w-0">
                            <div class="text-sm font-medium truncate" style="color:#181a1c;">{{ task.title }}</div>
                            <div class="text-xs" style="color:#72777d;">
                                {{ task.lead?.business_name || 'General' }} · Due {{ fmtDate(task.due_date) }}
                            </div>
                        </div>
                    </li>
                </ul>
            </div>

            <div class="bg-white rounded-xl p-5" style="box-shadow:0 1px 3px rgba(16,24,40,.08);">
                <div class="text-sm font-semibold mb-4" style="color:#181a1c;">Recent Emails</div>
                <div v-if="recent_sends.length === 0" class="text-sm py-4 text-center" style="color:#72777d;">
                    No emails sent yet.
                </div>
                <ul v-else class="space-y-3">
                    <li v-for="send in recent_sends" :key="send.id" class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="text-sm font-medium truncate" style="color:#181a1c;">{{ send.subject }}</div>
                            <div class="text-xs truncate" style="color:#72777d;">
                                {{ send.lead?.business_name || '—' }} · {{ fmtDateTime(send.sent_at || send.created_at) }}
                            </div>
                        </div>
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-full shrink-0"
                            :style="`background:${pill(statusColors, send.status).bg}; color:${pill(statusColors, send.status).fg};`">
                            {{ send.status }}
                        </span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>
