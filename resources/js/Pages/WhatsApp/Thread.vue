<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref, computed, nextTick, onMounted } from 'vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    lead: { type: Object, required: true },
    messages: { type: Array, default: () => [] },
    templates: { type: Array, default: () => [] },
    stub: { type: Boolean, default: false },
});

const mode = ref('text'); // 'text' | 'template'
const selectedTemplate = ref('');
const text = ref('');
const sending = ref(false);
const chatBox = ref(null);
const errors = ref({});

const send = () => {
    const payload = {
        lead_id: props.lead.id,
        template_name: mode.value === 'template' ? selectedTemplate.value : null,
        body: mode.value === 'text' ? text.value : null,
    };
    if (mode.value === 'template' && !selectedTemplate.value) return;
    if (mode.value === 'text' && !text.value.trim()) return;

    sending.value = true;
    errors.value = {};
    router.post(route('whatsapp.send'), payload, {
        preserveScroll: true,
        onSuccess: () => {
            text.value = '';
            selectedTemplate.value = '';
            nextTick(() => chatBox.value && (chatBox.value.scrollTop = chatBox.value.scrollHeight));
        },
        onError: (e) => { errors.value = e; },
        onFinish: () => { sending.value = false; },
    });
};

const fmtTime = (d) => d ? new Date(d.replace(' ', 'T')).toLocaleString() : '';

const statusPill = (status) => {
    const map = {
        sent: 'background:#eaf7f1;color:#268866;',
        delivered: 'background:#eaf7f1;color:#268866;',
        read: 'background:#eaf7f1;color:#268866;',
        received: 'background:#edf5ff;color:#3178c6;',
        stubbed: 'background:#fff6dc;color:#ad6c14;',
        failed: 'background:#fff0f0;color:#c64f4f;',
    };
    return map[status] || 'background:#f2f3f4;color:#72777d;';
};

const canSend = computed(() =>
    mode.value === 'template' ? !!selectedTemplate.value : !!text.value.trim()
);

onMounted(() => {
    if (chatBox.value) chatBox.value.scrollTop = chatBox.value.scrollHeight;
});
</script>

<template>
    <div class="max-w-3xl mx-auto">
        <!-- Header -->
        <div class="flex items-center gap-4 mb-4">
            <Link :href="route('whatsapp.index')" class="text-sm font-semibold" style="color:#72777d;">← All chats</Link>
            <span class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold" style="background:#f26a2e;">
                {{ (lead.business_name || 'W')[0].toUpperCase() }}
            </span>
            <div>
                <h1 class="text-xl font-bold" style="color:#181a1c;">{{ lead.business_name }}</h1>
                <p class="text-sm" style="color:#72777d;">
                    {{ lead.contact_person || '—' }} · {{ lead.phone || 'no phone' }} · {{ lead.city || '—' }}
                </p>
            </div>
        </div>

        <div v-if="stub" class="mb-4 rounded-xl px-4 py-3 text-sm font-medium"
            style="background:#fff6dc;color:#ad6c14;">
            STUB MODE — WhatsApp credentials are not configured, so messages are logged only and never actually sent.
        </div>

        <div class="bg-white rounded-xl p-6" style="box-shadow:0 1px 3px rgba(0,0,0,.06);">
            <!-- Chat -->
            <div ref="chatBox" class="h-[420px] overflow-y-auto px-2 py-4 space-y-3 rounded-xl mb-6" style="background:#f7f8f9;">
                <div v-if="!messages.length" class="text-sm text-center py-16" style="color:#72777d;">
                    No messages yet. Say hello below.
                </div>
                <div v-for="m in messages" :key="m.id" class="flex" :class="m.direction === 'out' ? 'justify-end' : 'justify-start'">
                    <div class="max-w-[75%] rounded-xl px-4 py-2.5"
                        :style="m.direction === 'out'
                            ? 'background:#f26a2e;color:#fff;border-bottom-right-radius:4px;'
                            : 'background:#fff;color:#181a1c;border:1px solid #e5e7e9;border-bottom-left-radius:4px;'">
                        <p class="text-sm whitespace-pre-wrap">{{ m.body }}</p>
                        <div class="flex items-center gap-2 mt-1 justify-end">
                            <span class="text-[11px]" :style="m.direction === 'out' ? 'color:rgba(255,255,255,.8);' : 'color:#72777d;'">{{ fmtTime(m.created_at) }}</span>
                            <span v-if="m.direction === 'out'" class="text-[11px] px-1.5 py-0.5 rounded-full font-semibold"
                                :style="statusPill(m.status)">{{ m.status }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Composer -->
            <div class="rounded-xl p-4" style="border:1px solid #e5e7e9;">
                <div class="flex gap-2 mb-3">
                    <button @click="mode = 'text'" class="px-3 py-1.5 rounded-lg text-sm font-semibold"
                        :style="mode === 'text' ? 'background:#f26a2e;color:#fff;' : 'background:#f2f3f4;color:#72777d;'">Free text</button>
                    <button @click="mode = 'template'" class="px-3 py-1.5 rounded-lg text-sm font-semibold"
                        :style="mode === 'template' ? 'background:#f26a2e;color:#fff;' : 'background:#f2f3f4;color:#72777d;'">Template</button>
                </div>

                <div v-if="mode === 'template'" class="mb-3">
                    <select v-model="selectedTemplate" class="w-full rounded-lg text-sm px-3 py-2.5"
                        style="border:1px solid #e5e7e9;color:#181a1c;">
                        <option value="" disabled>Choose a WhatsApp template…</option>
                        <option v-for="t in templates" :key="t.id" :value="t.name">{{ t.name }} ({{ t.language }})</option>
                    </select>
                    <p v-if="!templates.length" class="text-xs mt-1" style="color:#72777d;">No templates found — add them in Settings first.</p>
                </div>

                <div v-if="mode === 'text'" class="mb-3">
                    <textarea v-model="text" rows="3" placeholder="Type your message…"
                        class="w-full rounded-lg text-sm px-3 py-2.5"
                        style="border:1px solid #e5e7e9;color:#181a1c;"></textarea>
                </div>

                <div v-if="errors.send" class="text-sm mb-3" style="color:#c64f4f;">{{ errors.send }}</div>

                <div class="flex justify-end">
                    <button @click="send" :disabled="sending || !canSend"
                        class="px-6 py-2.5 rounded-lg text-sm font-semibold text-white transition disabled:opacity-50"
                        style="background:#f26a2e;">
                        {{ sending ? 'Sending…' : 'Send' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
