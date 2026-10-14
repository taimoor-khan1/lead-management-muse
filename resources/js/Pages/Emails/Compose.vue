<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    leads: Array,
    templates: Array,
    selectedLeadId: Number,
    selectedTemplateId: Number,
});

const form = useForm({
    lead_id: props.selectedLeadId || '',
    email_template_id: props.selectedTemplateId || '',
    subject: '',
    body: '',
    scheduled_at: '',
});

const mode = ref('now');

const lead = computed(() => props.leads.find((l) => l.id == form.lead_id));

watch(
    () => form.email_template_id,
    (id) => {
        const t = props.templates.find((x) => x.id == id);
        if (t) {
            form.subject = t.subject;
            form.body = t.body;
        }
    },
    { immediate: true }
);

const replaceVars = (text) =>
    String(text ?? '').replace(/{{\s*([a-z_]+)\s*}}/g, (_, k) => lead.value?.[k] ?? '');

const previewSubject = computed(() => replaceVars(form.subject));
const previewBody = computed(() => replaceVars(form.body));

const submit = () => {
    form.transform((data) => ({
        ...data,
        scheduled_at: mode.value === 'later' ? data.scheduled_at : null,
    })).post(route('emails.send'));
};

const inputCls = 'w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2';
const ringStyle = 'border-color:#e5e7e9;color:#181a1c;--tw-ring-color:#f26a2e;';
</script>

<template>
    <Head title="Compose Email" />

    <div class="mb-6">
        <h1 class="text-2xl font-bold" style="color:#181a1c;">Compose Email</h1>
        <p class="text-sm mt-1" style="color:#72777d;">Pick a lead and a template, preview the personalization, then send or schedule.</p>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl border shadow-sm p-6" style="border-color:#e5e7e9;">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold mb-1" style="color:#181a1c;">Lead</label>
                    <select v-model="form.lead_id" :class="inputCls" :style="ringStyle">
                        <option value="" disabled>Select a lead…</option>
                        <option v-for="l in leads" :key="l.id" :value="l.id">
                            {{ l.business_name }} — {{ l.email }}
                        </option>
                    </select>
                    <p v-if="form.errors.lead_id" class="text-xs mt-1" style="color:#c64f4f;">{{ form.errors.lead_id }}</p>
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-1" style="color:#181a1c;">Template (optional)</label>
                    <select v-model="form.email_template_id" :class="inputCls" :style="ringStyle">
                        <option value="">No template — write custom</option>
                        <option v-for="t in templates" :key="t.id" :value="t.id">{{ t.name }}</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-1" style="color:#181a1c;">Subject</label>
                    <input v-model="form.subject" type="text" :class="inputCls" :style="ringStyle" />
                    <p v-if="form.errors.subject" class="text-xs mt-1" style="color:#c64f4f;">{{ form.errors.subject }}</p>
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-1" style="color:#181a1c;">Body</label>
                    <textarea v-model="form.body" rows="9" :class="inputCls" :style="ringStyle"></textarea>
                    <p v-if="form.errors.body" class="text-xs mt-1" style="color:#c64f4f;">{{ form.errors.body }}</p>
                    <p class="text-xs mt-1" style="color:#72777d;">
                        Variables: {{business_name}}, {{contact_person}}, {{city}}, {{industry}}, {{suggested_service}}
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-2" style="color:#181a1c;">Delivery</label>
                    <div class="flex gap-2 mb-3">
                        <button type="button" @click="mode = 'now'" class="px-4 py-2 rounded-lg text-sm font-semibold border"
                            :style="mode === 'now' ? 'background:#f26a2e;border-color:#f26a2e;color:#fff;' : 'border-color:#e5e7e9;color:#72777d;'">
                            Send now
                        </button>
                        <button type="button" @click="mode = 'later'" class="px-4 py-2 rounded-lg text-sm font-semibold border"
                            :style="mode === 'later' ? 'background:#f26a2e;border-color:#f26a2e;color:#fff;' : 'border-color:#e5e7e9;color:#72777d;'">
                            Schedule
                        </button>
                    </div>
                    <input v-if="mode === 'later'" v-model="form.scheduled_at" type="datetime-local" :class="inputCls" :style="ringStyle" />
                    <p v-if="form.errors.scheduled_at" class="text-xs mt-1" style="color:#c64f4f;">{{ form.errors.scheduled_at }}</p>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button @click="submit" :disabled="form.processing" class="px-5 py-2 rounded-lg text-sm font-semibold text-white disabled:opacity-60" style="background:#f26a2e;">
                        {{ mode === 'now' ? 'Send Email' : 'Schedule Email' }}
                    </button>
                    <Link :href="route('emails.index')" class="text-sm font-medium" style="color:#72777d;">Cancel</Link>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border shadow-sm p-6" style="border-color:#e5e7e9;">
            <h2 class="text-sm font-semibold mb-1" style="color:#181a1c;">Live preview</h2>
            <p class="text-xs mb-4" style="color:#72777d;">
                {{ lead ? `Rendered for ${lead.business_name}.` : 'Select a lead to see personalization.' }}
            </p>
            <div class="rounded-lg border p-4" style="border-color:#e5e7e9;background:#fafbfc;">
                <p class="text-sm font-semibold mb-3" style="color:#181a1c;">{{ previewSubject || '(subject)' }}</p>
                <p class="text-sm whitespace-pre-wrap" style="color:#181a1c;">{{ previewBody || '(body)' }}</p>
            </div>
        </div>
    </div>
</template>
