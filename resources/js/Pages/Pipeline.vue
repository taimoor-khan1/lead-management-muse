<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    columns: { type: Array, required: true },
});

const state = reactive({
    cols: props.columns.map((c) => ({ key: c.key, label: c.label, leads: [...c.leads] })),
});

const stageColors = {
    new: { bg: '#edf5ff', fg: '#3178c6' },
    contacted: { bg: '#fff6dc', fg: '#ad6c14' },
    replied: { bg: '#edf5ff', fg: '#3178c6' },
    proposal: { bg: '#fff6dc', fg: '#ad6c14' },
    won: { bg: '#eaf7f1', fg: '#268866' },
    lost: { bg: '#fff0f0', fg: '#c64f4f' },
};

const serviceColor = { bg: '#fff0e8', fg: '#a73c10' };

let dragLeadId = null;
let dragFromKey = null;

const onDragStart = (e, lead, fromKey) => {
    dragLeadId = lead.id;
    dragFromKey = fromKey;
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', String(lead.id));
};

const onDragOver = (e) => {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
};

const onDrop = (e, toKey) => {
    e.preventDefault();
    if (dragLeadId === null || dragFromKey === toKey) {
        dragLeadId = null;
        dragFromKey = null;
        return;
    }

    const fromCol = state.cols.find((c) => c.key === dragFromKey);
    const toCol = state.cols.find((c) => c.key === toKey);
    const idx = fromCol.leads.findIndex((l) => l.id === dragLeadId);
    if (idx === -1) return;

    // Optimistic UI update
    const [lead] = fromCol.leads.splice(idx, 1);
    const prevStage = lead.stage;
    lead.stage = toKey;
    toCol.leads.unshift(lead);

    router.post(
        route('pipeline.move'),
        { lead_id: dragLeadId, stage: toKey },
        {
            preserveState: true,
            preserveScroll: true,
            onError: () => {
                // Roll back on failure
                const back = toCol.leads.findIndex((l) => l.id === dragLeadId);
                if (back !== -1) {
                    const [rolled] = toCol.leads.splice(back, 1);
                    rolled.stage = prevStage;
                    fromCol.leads.splice(idx, 0, rolled);
                }
            },
        }
    );

    dragLeadId = null;
    dragFromKey = null;
};
</script>

<template>
    <Head title="Pipeline" />

    <div class="mb-6">
        <h1 class="text-2xl font-bold" style="color:#181a1c;">Pipeline</h1>
        <p class="text-sm mt-1" style="color:#72777d;">Drag a card to move a lead between stages</p>
    </div>

    <div class="flex gap-4 overflow-x-auto pb-4 items-start">
        <div v-for="col in state.cols" :key="col.key"
            class="w-72 shrink-0 rounded-xl p-3 bg-white"
            style="box-shadow:0 1px 3px rgba(16,24,40,.08);"
            @dragover="onDragOver"
            @drop="onDrop($event, col.key)">
            <div class="flex items-center justify-between px-1 py-2">
                <span class="text-sm font-semibold" style="color:#181a1c;">{{ col.label }}</span>
                <span class="text-xs font-bold px-2 py-0.5 rounded-full"
                    :style="`background:${stageColors[col.key].bg}; color:${stageColors[col.key].fg};`">
                    {{ col.leads.length }}
                </span>
            </div>

            <div class="space-y-3 min-h-[120px] rounded-lg p-1" style="background:#f8f9fa;">
                <div v-if="col.leads.length === 0" class="text-xs text-center py-6" style="color:#b0b5ba;">
                    Drop cards here
                </div>
                <div v-for="lead in col.leads" :key="lead.id"
                    draggable="true"
                    @dragstart="onDragStart($event, lead, col.key)"
                    class="bg-white rounded-xl p-4 cursor-grab active:cursor-grabbing border"
                    style="border-color:#e5e7e9; box-shadow:0 1px 2px rgba(16,24,40,.06);">
                    <div class="text-sm font-semibold mb-2" style="color:#181a1c;">{{ lead.business_name }}</div>
                    <div class="flex flex-wrap gap-1.5 mb-2">
                        <span v-if="lead.industry"
                            class="text-[11px] font-semibold px-2 py-0.5 rounded-full"
                            :style="`background:${stageColors[col.key].bg}; color:${stageColors[col.key].fg};`">
                            {{ lead.industry }}
                        </span>
                        <span v-if="lead.suggested_service"
                            class="text-[11px] font-semibold px-2 py-0.5 rounded-full"
                            :style="`background:${serviceColor.bg}; color:${serviceColor.fg};`">
                            {{ lead.suggested_service }}
                        </span>
                    </div>
                    <div class="text-xs flex items-center gap-3" style="color:#72777d;">
                        <span v-if="lead.city">{{ lead.city }}</span>
                        <span v-if="lead.phone" class="truncate">{{ lead.phone }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
