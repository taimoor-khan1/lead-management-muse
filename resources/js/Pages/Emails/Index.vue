<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

defineOptions({ layout: AppLayout });

const props = defineProps({ sends: Object, filters: Object });

const status = ref(props.filters?.status ?? '');
const search = ref(props.filters?.search ?? '');

const STATUSES = ['queued', 'scheduled', 'sending', 'sent', 'opened', 'clicked', 'failed', 'replied'];

const apply = () => {
    router.get(route('emails.index'), { status: status.value, search: search.value }, { preserveState: true, replace: true });
};

const pill = (s) => {
    const map = {
        queued: ['#ad6c14', '#fff6dc'],
        scheduled: ['#3178c6', '#edf5ff'],
        sending: ['#3178c6', '#edf5ff'],
        sent: ['#268866', '#eaf7f1'],
        opened: ['#3178c6', '#edf5ff'],
        clicked: ['#268866', '#eaf7f1'],
        replied: ['#268866', '#eaf7f1'],
        failed: ['#c64f4f', '#fff0f0'],
    };
    const [color, bg] = map[s] || map.queued;
    return { color, background: bg };
};

const fmtDate = (d) => d ? new Date(d.replace(' ', 'T')).toLocaleString() : '—';
</script>

<template>
    <Head title="Emails" />

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold" style="color:#181a1c;">Emails</h1>
            <p class="text-sm mt-1" style="color:#72777d;">Sent log with open and click tracking.</p>
        </div>
        <Link :href="route('emails.compose')" class="px-4 py-2 rounded-lg text-sm font-semibold text-white" style="background:#f26a2e;">
            + Compose
        </Link>
    </div>

    <div class="bg-white rounded-xl border shadow-sm p-4 mb-4 flex flex-col sm:flex-row gap-3" style="border-color:#e5e7e9;">
        <input v-model="search" @keyup.enter="apply" type="text" placeholder="Search subject, business, email..."
            class="flex-1 rounded-lg border px-3 py-2 text-sm focus:outline-none" style="border-color:#e5e7e9;color:#181a1c;" />
        <select v-model="status" @change="apply" class="rounded-lg border px-3 py-2 text-sm focus:outline-none" style="border-color:#e5e7e9;color:#181a1c;">
            <option value="">All statuses</option>
            <option v-for="s in STATUSES" :key="s" :value="s">{{ s }}</option>
        </select>
        <button @click="apply" class="px-4 py-2 rounded-lg text-sm font-semibold border" style="border-color:#e5e7e9;color:#181a1c;">Filter</button>
    </div>

    <div class="bg-white rounded-xl border shadow-sm overflow-hidden" style="border-color:#e5e7e9;">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left border-b" style="border-color:#e5e7e9;">
                    <th class="px-5 py-3 font-semibold" style="color:#72777d;">Lead</th>
                    <th class="px-5 py-3 font-semibold" style="color:#72777d;">Subject</th>
                    <th class="px-5 py-3 font-semibold" style="color:#72777d;">Status</th>
                    <th class="px-5 py-3 font-semibold text-center" style="color:#72777d;">Opens</th>
                    <th class="px-5 py-3 font-semibold text-center" style="color:#72777d;">Clicks</th>
                    <th class="px-5 py-3 font-semibold" style="color:#72777d;">Sent at</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="s in sends.data" :key="s.id" class="border-b last:border-0" style="border-color:#e5e7e9;">
                    <td class="px-5 py-3">
                        <div class="font-semibold" style="color:#181a1c;">{{ s.lead?.business_name ?? '—' }}</div>
                        <div class="text-xs" style="color:#72777d;">{{ s.lead?.email ?? '' }}</div>
                    </td>
                    <td class="px-5 py-3 max-w-[320px] truncate" style="color:#181a1c;">{{ s.subject }}</td>
                    <td class="px-5 py-3">
                        <span class="text-xs font-semibold px-2 py-1 rounded-full" :style="pill(s.status)">{{ s.status }}</span>
                    </td>
                    <td class="px-5 py-3 text-center" style="color:#181a1c;">{{ s.opens }}</td>
                    <td class="px-5 py-3 text-center" style="color:#181a1c;">{{ s.clicks }}</td>
                    <td class="px-5 py-3 whitespace-nowrap" style="color:#72777d;">{{ fmtDate(s.sent_at) }}</td>
                </tr>
                <tr v-if="!sends.data.length">
                    <td colspan="6" class="px-5 py-10 text-center" style="color:#72777d;">No emails yet. Compose your first outreach.</td>
                </tr>
            </tbody>
        </table>

        <div v-if="sends.links && sends.links.length > 3" class="flex justify-center gap-1 px-5 py-4 border-t" style="border-color:#e5e7e9;">
            <Link v-for="link in sends.links" :key="link.label" :href="link.url || '#'" v-html="link.label"
                class="px-3 py-1 rounded-md text-sm" :class="{ 'pointer-events-none opacity-40': !link.url }"
                :style="link.active ? 'background:#f26a2e;color:#fff;font-weight:600;' : 'color:#72777d;'" />
        </div>
    </div>
</template>
