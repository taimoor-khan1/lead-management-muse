<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    funnel: { type: Array, default: () => [] },
    industryCounts: { type: Array, default: () => [] },
    campaignStats: { type: Array, default: () => [] },
    proposalStats: { type: Object, default: () => ({}) },
    wonRevenue: { type: Number, default: 0 },
    tasksStats: { type: Object, default: () => ({ open: 0, overdue: 0, done: 0 }) },
});

const maxFunnel = computed(() => Math.max(1, ...props.funnel.map((f) => f.count)));
const totalLeads = computed(() => props.funnel.reduce((s, f) => s + f.count, 0));

const stageColors = {
    new: '#3178c6', contacted: '#ad6c14', replied: '#268866',
    proposal: '#7c5cc6', won: '#1e8e5a', lost: '#c64f4f',
};

const pkr = (n) => 'PKR ' + Number(n || 0).toLocaleString('en-PK');

const proposalStatuses = computed(() =>
    ['draft', 'sent', 'accepted', 'declined'].map((s) => ({ status: s, count: props.proposalStats[s] || 0 }))
);

const proposalPill = (s) => ({
    draft: 'background:#eef0f2; color:#72777d;',
    sent: 'background:#edf5ff; color:#3178c6;',
    accepted: 'background:#eaf7f1; color:#268866;',
    declined: 'background:#fff0f0; color:#c64f4f;',
}[s]);

const taskCards = computed(() => [
    { label: 'Open tasks', value: props.tasksStats.open, style: 'background:#edf5ff; color:#3178c6;' },
    { label: 'Overdue tasks', value: props.tasksStats.overdue, style: 'background:#fff0f0; color:#c64f4f;' },
    { label: 'Completed', value: props.tasksStats.done, style: 'background:#eaf7f1; color:#268866;' },
]);
</script>

<template>
    <Head title="Reports" />

    <div class="mb-6">
        <h1 class="text-2xl font-bold" style="color:#181a1c;">Reports</h1>
        <p class="text-sm mt-1" style="color:#72777d;">Pipeline, campaigns, proposals and tasks at a glance.</p>
    </div>

    <!-- Funnel -->
    <div class="bg-white rounded-xl p-5 mb-6" style="box-shadow:0 1px 3px rgba(0,0,0,.06);">
        <h2 class="text-base font-bold mb-1" style="color:#181a1c;">Lead funnel</h2>
        <p class="text-xs mb-4" style="color:#72777d;">{{ totalLeads }} leads total</p>
        <div class="space-y-3">
            <div v-for="f in funnel" :key="f.stage">
                <div class="flex items-center justify-between text-sm mb-1">
                    <span class="font-medium capitalize" style="color:#181a1c;">{{ f.stage }}</span>
                    <span class="font-semibold" style="color:#72777d;">{{ f.count }}</span>
                </div>
                <div class="h-3 rounded-full" style="background:#eef0f2;">
                    <div class="h-3 rounded-full transition-all" :style="'width:' + (f.count / maxFunnel * 100) + '%; background:' + (stageColors[f.stage] || '#72777d') + ';'"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Industries -->
        <div class="bg-white rounded-xl p-5" style="box-shadow:0 1px 3px rgba(0,0,0,.06);">
            <h2 class="text-base font-bold mb-4" style="color:#181a1c;">Top industries</h2>
            <div v-if="!industryCounts.length" class="text-sm" style="color:#72777d;">No industry data yet.</div>
            <table v-else class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs" style="color:#72777d;">
                        <th class="pb-2 font-semibold">Industry</th>
                        <th class="pb-2 font-semibold text-right">Leads</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in industryCounts" :key="row.industry" class="border-t" style="border-color:#eef0f2;">
                        <td class="py-2 font-medium" style="color:#181a1c;">{{ row.industry }}</td>
                        <td class="py-2 text-right font-semibold" style="color:#181a1c;">{{ row.count }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Tasks -->
        <div class="bg-white rounded-xl p-5" style="box-shadow:0 1px 3px rgba(0,0,0,.06);">
            <h2 class="text-base font-bold mb-4" style="color:#181a1c;">Tasks</h2>
            <div class="grid grid-cols-3 gap-3">
                <div v-for="c in taskCards" :key="c.label" class="rounded-xl p-4 text-center" :style="c.style">
                    <div class="text-2xl font-bold">{{ c.value }}</div>
                    <div class="text-xs font-medium mt-1">{{ c.label }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Campaigns -->
    <div class="bg-white rounded-xl p-5 mb-6" style="box-shadow:0 1px 3px rgba(0,0,0,.06);">
        <h2 class="text-base font-bold mb-4" style="color:#181a1c;">Campaign performance</h2>
        <div v-if="!campaignStats.length" class="text-sm" style="color:#72777d;">No campaigns yet.</div>
        <table v-else class="w-full text-sm">
            <thead>
                <tr class="text-left text-xs" style="color:#72777d;">
                    <th class="pb-2 font-semibold">Campaign</th>
                    <th class="pb-2 font-semibold">Status</th>
                    <th class="pb-2 font-semibold text-right">Sent</th>
                    <th class="pb-2 font-semibold text-right">Opens</th>
                    <th class="pb-2 font-semibold text-right">Clicks</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="c in campaignStats" :key="c.name" class="border-t" style="border-color:#eef0f2;">
                    <td class="py-2 font-medium" style="color:#181a1c;">{{ c.name }}</td>
                    <td class="py-2 capitalize" style="color:#72777d;">{{ c.status }}</td>
                    <td class="py-2 text-right font-semibold" style="color:#181a1c;">{{ c.sent }}</td>
                    <td class="py-2 text-right" style="color:#181a1c;">{{ c.opens }}</td>
                    <td class="py-2 text-right" style="color:#181a1c;">{{ c.clicks }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Proposals -->
    <div class="bg-white rounded-xl p-5" style="box-shadow:0 1px 3px rgba(0,0,0,.06);">
        <h2 class="text-base font-bold mb-4" style="color:#181a1c;">Proposals</h2>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
            <div class="rounded-xl p-4 text-center" style="background:#fff0e8;">
                <div class="text-xl font-bold" style="color:#a73c10;">{{ pkr(wonRevenue) }}</div>
                <div class="text-xs font-medium mt-1" style="color:#a73c10;">Won revenue</div>
            </div>
            <div v-for="p in proposalStatuses" :key="p.status" class="rounded-xl p-4 text-center" :style="proposalPill(p.status)">
                <div class="text-xl font-bold">{{ p.count }}</div>
                <div class="text-xs font-medium mt-1 capitalize">{{ p.status }}</div>
            </div>
        </div>
    </div>
</template>
