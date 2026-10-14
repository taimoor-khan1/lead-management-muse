<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';

defineOptions({ layout: AppLayout });

defineProps({
    threads: { type: Array, default: () => [] },
    stub: { type: Boolean, default: false },
});

const fmt = (d) => d ? new Date(d.replace(' ', 'T')).toLocaleString() : '—';
const truncate = (s, n = 60) => !s ? '' : (s.length > n ? s.slice(0, n) + '…' : s);
</script>

<template>
    <div>
        <div class="flex items-center justify-between mb-1">
            <h1 class="text-2xl font-bold" style="color:#181a1c;">WhatsApp</h1>
            <Link :href="route('inbox.index')" class="text-sm font-semibold" style="color:#f26a2e;">Open unified inbox</Link>
        </div>
        <p class="text-sm mb-6" style="color:#72777d;">Conversations with your leads on WhatsApp.</p>

        <div v-if="stub" class="mb-4 rounded-xl px-4 py-3 text-sm font-medium"
            style="background:#fff6dc;color:#ad6c14;">
            STUB MODE — WHATSAPP_TOKEN or WHATSAPP_PHONE_ID is not configured, so messages are logged only and never actually sent.
        </div>

        <div class="bg-white rounded-xl p-6" style="box-shadow:0 1px 3px rgba(0,0,0,.06);">
            <div v-if="!threads.length" class="text-sm py-10 text-center" style="color:#72777d;">
                No WhatsApp conversations yet. New replies from leads will appear here automatically.
            </div>
            <div v-else class="divide-y" style="border-color:#f0f1f2;">
                <Link v-for="t in threads" :key="t.id" :href="route('whatsapp.thread', t.id)"
                    class="flex items-center gap-4 py-4 px-2 -mx-2 rounded-xl hover:bg-[#f7f8f9] transition">
                    <span class="w-11 h-11 rounded-full flex items-center justify-center text-white font-bold shrink-0"
                        style="background:#f26a2e;">
                        {{ (t.business_name || 'W')[0].toUpperCase() }}
                    </span>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-semibold truncate" style="color:#181a1c;">{{ t.business_name }}</span>
                            <span class="text-xs whitespace-nowrap" style="color:#72777d;">{{ fmt(t.latest_message?.created_at) }}</span>
                        </div>
                        <div class="text-sm truncate mt-0.5" style="color:#72777d;">
                            <span v-if="t.latest_message?.direction === 'out'" class="font-semibold">You: </span>{{ truncate(t.latest_message?.body) }}
                        </div>
                    </div>
                </Link>
            </div>
        </div>
    </div>
</template>
