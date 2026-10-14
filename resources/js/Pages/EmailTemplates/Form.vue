<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineOptions({ layout: AppLayout });

const props = defineProps({ template: Object });

const form = useForm({
    name: props.template?.name ?? '',
    subject: props.template?.subject ?? '',
    body: props.template?.body ?? '',
});

const VARIABLES = ['business_name', 'contact_person', 'city', 'industry', 'suggested_service'];
const varLabel = (v) => '{{' + v + '}}';

const sampleLead = {
    business_name: 'Green Acres Farmhouse',
    contact_person: 'Bilal Ahmed',
    city: 'Murree',
    industry: 'Hospitality',
    suggested_service: 'Website Revamp',
};

const bodyRef = ref(null);
let cursorPos = 0;

const trackCursor = () => {
    if (bodyRef.value) cursorPos = bodyRef.value.selectionStart ?? form.body.length;
};

const insertVar = (v) => {    const tag = `{{${v}}}`;
    const before = form.body.slice(0, cursorPos);
    const after = form.body.slice(cursorPos);
    form.body = before + tag + after;
    cursorPos += tag.length;
    if (bodyRef.value) {
        bodyRef.value.focus();
        bodyRef.value.setSelectionRange(cursorPos, cursorPos);
    }
};

const replaceVars = (text) =>
    String(text ?? '').replace(/{{\s*([a-z_]+)\s*}}/g, (_, k) => sampleLead[k] ?? '');

const previewSubject = computed(() => replaceVars(form.subject));
const previewBody = computed(() => replaceVars(form.body));

const submit = () => {
    if (props.template) {
        form.put(route('email-templates.update', props.template.id));
    } else {
        form.post(route('email-templates.store'));
    }
};

const inputCls = 'w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2';
</script>

<template>
    <Head :title="template ? 'Edit Template' : 'New Template'" />

    <div class="mb-6">
        <h1 class="text-2xl font-bold" style="color:#181a1c;">{{ template ? 'Edit Template' : 'New Template' }}</h1>
        <p class="text-sm mt-1" style="color:#72777d;">Use {{variable}} placeholders — they get replaced with each lead's data when sending.</p>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl border shadow-sm p-6" style="border-color:#e5e7e9;">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold mb-1" style="color:#181a1c;">Template name</label>
                    <input v-model="form.name" type="text" :class="inputCls" style="border-color:#e5e7e9;color:#181a1c;--tw-ring-color:#f26a2e;" placeholder="e.g. Intro — Website Revamp" />
                    <p v-if="form.errors.name" class="text-xs mt-1" style="color:#c64f4f;">{{ form.errors.name }}</p>
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1" style="color:#181a1c;">Subject</label>
                    <input v-model="form.subject" type="text" :class="inputCls" style="border-color:#e5e7e9;color:#181a1c;--tw-ring-color:#f26a2e;" placeholder="Quick idea for {{business_name}}" />
                    <p v-if="form.errors.subject" class="text-xs mt-1" style="color:#c64f4f;">{{ form.errors.subject }}</p>
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1" style="color:#181a1c;">Body</label>
                    <div class="flex flex-wrap gap-2 mb-2">
                        <button v-for="v in VARIABLES" :key="v" type="button" @click="insertVar(v)"
                            class="text-xs font-medium px-2 py-1 rounded-md border" style="border-color:#e5e7e9;color:#3178c6;background:#edf5ff;">
                            {{ varLabel(v) }}
                        </button>
                    </div>
                    <textarea ref="bodyRef" v-model="form.body" rows="10" :class="inputCls" @click="trackCursor" @keyup="trackCursor"
                        style="border-color:#e5e7e9;color:#181a1c;--tw-ring-color:#f26a2e;" placeholder="Hi {{contact_person}}, ..."></textarea>
                    <p v-if="form.errors.body" class="text-xs mt-1" style="color:#c64f4f;">{{ form.errors.body }}</p>
                </div>
                <div class="flex items-center gap-3 pt-2">
                    <button @click="submit" :disabled="form.processing" class="px-5 py-2 rounded-lg text-sm font-semibold text-white disabled:opacity-60" style="background:#f26a2e;">
                        {{ template ? 'Save Changes' : 'Create Template' }}
                    </button>
                    <Link :href="route('email-templates.index')" class="text-sm font-medium" style="color:#72777d;">Cancel</Link>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border shadow-sm p-6" style="border-color:#e5e7e9;">
            <h2 class="text-sm font-semibold mb-1" style="color:#181a1c;">Live preview</h2>
            <p class="text-xs mb-4" style="color:#72777d;">Rendered with a sample lead.</p>
            <div class="rounded-lg border p-4" style="border-color:#e5e7e9;background:#fafbfc;">
                <p class="text-sm font-semibold mb-3" style="color:#181a1c;">{{ previewSubject || '(subject)' }}</p>
                <p class="text-sm whitespace-pre-wrap" style="color:#181a1c;">{{ previewBody || '(body)' }}</p>
            </div>
        </div>
    </div>
</template>
