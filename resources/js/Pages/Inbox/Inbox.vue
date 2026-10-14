<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    emails: { type: Array, default: () => [] },
    whatsapps: { type: Array, default: () => [] },
    counts: { type: Object, default: () => ({ emails: 0, whatsapp: 0 }) },
});

const activeTab = ref('whatsapp');

const statusPill = (status) => {
    const map = {
        sent: 'background:#eaf7f1;color:#268866;',
        delivered: 'background:#eaf7f1;color:#268866;',
        read: 'background:#eaf7f1;color:#268866;',
        replied: 'background:#eaf7f1;color:#268866;',
        opened: 'background:#edf5ff;color:#3178c6;',
        clicked: 'background:#edf5ff;color:#3178c6;',
        received: 'background:#edf5ff;color:#3178c6;',
        stubbed: 'background:#fff6dc;color:#ad6c14;',
        queued: 'background:#fff6dc;color:#ad6c14;',
        sending: 'background:#fff6dc;color:#ad6c14;',
        scheduled: 'background:#fff6dc;color:#ad6c14;',
        failed: 'background:#fff0f0;color:#c64f4f;',
    };
    return map[status] || 'background:#f2f3f4;color:#72777d;';
};

const fmt = (d) => d ? new Date(d.replace(' ', 'T')).toLocaleString() : '—';

const truncate = (s, n = 90) => !s ? '—' : (s.length > n ? s.slice(0, n) + '…' : s);

const markReplied = (id) => {
    if (confirm('Mark this email as replied?')) {
        router.post(route('emails.mark-replied', id));
    }
};

const tabs = computed(() => [
    { key: 'whatsapp', label: `WhatsApp (${props.counts.whatsapp})` },
    { key: 'emails', label: `Emails (${props.counts.emails})` },
]);
</script>

<template>
    <div>
        <h1 class="text-2xl font-bold mb-1" style="color:#181a1c;">Inbox</h1>
        <p class="text-sm mb-6" style="color:#72777d;">Every email and WhatsApp message in one place.</p>

        <div class="bg-white rounded-xl p-6" style="box-shadow:0 1px 3px rgba(0,0,0,.06);">
            <!-- Tabs -->
            <div class="flex gap-2 mb-6">
                <button v-for="t in tabs" :key="t.key" @click="activeTab = t.key"
                    class="px-4 py-2 rounded-lg text-sm font-semibold transition"
                    :style="activeTab === t.key
                        ? 'background:#f26a2e;color:#fff;'
                        : 'background:#f2f3f4;color:#72777d;'">
                    {{ t.label }}
                </button>
            </div>

            <!-- WhatsApp tab -->
            <div v-if="activeTab === 'whatsapp'">
                <div v-if="!whatsapps.length" class="text-sm py-10 text-center" style="color:#72777d;">
                    No WhatsApp messages yet. Connect your WhatsApp number in Settings to start receiving replies.
                </div>
                <div class="overflow-x-auto">
                    <table v-if="whatsapps.length" class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs uppercase" style="color:#72777d;border-bottom:1px solid #e5e7e9;">
                                <th class="py-3 pr-4 font-semibold">Lead</th>
                                <th class="py-3 pr-4 font-semibold">Direction</th>
                                <th class="py-3 pr-4 font-semibold">Message</th>
                                <th class="py-3 pr-4 font-semibold">Status</th>
                                <th class="py-3 font-semibold">Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="m in whatsapps" :key="m.id" style="border-bottom:1px solid #f0f1f2;">
                                <td class="py-3 pr-4">
                                    <Link v-if="m.lead" :href="route('whatsapp.thread', m.lead.id)"
                                        class="font-semibold hover:underline" style="color:#181a1c;">
                                        {{ m.lead.business_name }}
                                    </Link>
                                    <span v-else class="font-mono text-xs" style="color:#72777d;">{{ m.phone }}</span>
                                </td>
                                <td class="py-3 pr-4">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold"
                                        :style="m.direction === 'in' ? 'background:#edf5ff;color:#3178c6;' : 'background:#fff0e8;color:#a73c10;'">
                                        {{ m.direction === 'in' ? 'Incoming' : 'Outgoing' }}
                                    </span>
                                </td>
                                <td class="py-3 pr-4" style="color:#72777d;">{{ truncate(m.body) }}</td>
                                <td class="py-3 pr-4">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold" :style="statusPill(m.status)">{{ m.status }}</span>
                                </td>
                                <td class="py-3 text-xs whitespace-nowrap" style="color:#72777d;">{{ fmt(m.created_at) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Emails tab -->
            <div v-if="activeTab === 'emails'">
                <div v-if="!emails.length" class="text-sm py-10 text-center" style="color:#72777d;">
                    No emails sent yet.
                </div>
                <div class="overflow-x-auto">
                    <table v-if="emails.length" class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs uppercase" style="color:#72777d;border-bottom:1px solid #e5e7e9;">
                                <th class="py-3 pr-4 font-semibold">Lead</th>
                                <th class="py-3 pr-4 font-semibold">Subject</th>
                                <th class="py-3 pr-4 font-semibold">Status</th>
                                <th class="py-3 pr-4 font-semibold">Sent</th>
                                <th class="py-3 font-semibold">Reply</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="e in emails" :key="e.id" style="border-bottom:1px solid #f0f1f2;">
                                <td class="py-3 pr-4">
                                    <Link v-if="e.lead" :href="route('leads.show', e.lead.id)"
                                        class="font-semibold hover:underline" style="color:#181a1c;">
                                        {{ e.lead.business_name }}
                                    </Link>
                                    <span v-else style="color:#72777d;">—</span>
                                </td>
                                <td class="py-3 pr-4" style="color:#72777d;">{{ truncate(e.subject, 60) }}</td>
                                <td class="py-3 pr-4">
                                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold" :style="statusPill(e.status)">{{ e.status }}</span>
                                </td>
                                <td class="py-3 pr-4 text-xs whitespace-nowrap" style="color:#72777d;">{{ fmt(e.sent_at || e.created_at) }}</td>
                                <td class="py-3">
                                    <button v-if="e.status !== 'replied'" @click="markReplied(e.id)"
                                        class="text-xs font-semibold px-3 py-1.5 rounded-lg"
                                        style="background:#f2f3f4;color:#72777d;">
                                        Mark replied
                                    </button>
                                    <span v-else class="text-xs font-semibold" style="color:#268866;">Replied</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>
