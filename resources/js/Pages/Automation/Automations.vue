<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    automations: { type: Array, default: () => [] },
    emailTemplates: { type: Array, default: () => [] },
});

const page = usePage();
const runOutput = computed(() => page.props.flash || null);

const triggers = [
    { key: 'no_reply_days', label: 'No reply for N days' },
    { key: 'lead_created', label: 'Lead created' },
    { key: 'stage_changed', label: 'Stage changed' },
];
const actions = [
    { key: 'create_task', label: 'Create task' },
    { key: 'send_email', label: 'Send email' },
    { key: 'change_stage', label: 'Change stage' },
];
const stageOptions = ['new', 'contacted', 'replied', 'proposal', 'won', 'lost'];

const editing = reactive({ id: null });
const form = useForm({
    name: '', trigger: 'no_reply_days', action: 'create_task', active: true,
    config: { days: 7, task_title: 'Follow up', template_id: '', stage: '' },
});

const blankConfig = () => ({ days: 7, task_title: 'Follow up', template_id: '', stage: '' });

const startCreate = () => {
    editing.id = null;
    form.reset();
    form.clearErrors();
};

const startEdit = (a) => {
    editing.id = a.id;
    form.name = a.name;
    form.trigger = a.trigger;
    form.action = a.action;
    form.active = !!a.active;
    form.config = { ...blankConfig(), ...(a.config || {}) };
    form.clearErrors();
};

const submit = () => {
    const opts = { onSuccess: () => { editing.id = null; form.reset(); } };
    if (editing.id) form.patch(route('automations.update', editing.id), opts);
    else form.post(route('automations.store'), opts);
};

const remove = (a) => {
    if (confirm(`Delete automation "${a.name}"?`)) router.delete(route('automations.destroy', a.id));
};

const runNow = () => router.post(route('automations.run'));

const triggerLabel = (k) => triggers.find((t) => t.key === k)?.label || k;
const actionLabel = (k) => actions.find((a) => a.key === k)?.label || k;

const configSummary = (a) => {
    const c = a.config || {};
    const parts = [];
    if (c.days) parts.push(`${c.days} days`);
    if (c.task_title) parts.push(`task: "${c.task_title}"`);
    if (c.template_id) parts.push(`template #${c.template_id}`);
    if (c.stage) parts.push(`→ ${c.stage}`);
    return parts.join(' · ') || '—';
};

const fmtDateTime = (d) => d ? new Date(d.replace(' ', 'T')).toLocaleString('en-PK', { day: 'numeric', month: 'short', hour: 'numeric', minute: '2-digit' }) : 'Never';
</script>

<template>
    <Head title="Automation" />

    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold" style="color:#181a1c;">Automation</h1>
            <p class="text-sm mt-1" style="color:#72777d;">Rules that act on your leads automatically.</p>
        </div>
        <button @click="runNow" class="rounded-lg px-4 py-2 text-sm font-semibold text-white" style="background:#f26a2e;">
            Run now
        </button>
    </div>

    <div v-if="runOutput" class="bg-white rounded-xl p-4 mb-6 text-sm whitespace-pre-line"
        style="color:#181a1c; box-shadow:0 1px 3px rgba(0,0,0,.06);">
        {{ runOutput }}
    </div>

    <!-- Rule list -->
    <div class="bg-white rounded-xl mb-6 overflow-hidden" style="box-shadow:0 1px 3px rgba(0,0,0,.06);">
        <div v-if="!automations.length" class="p-6 text-sm" style="color:#72777d;">
            No automation rules yet. Create one below.
        </div>
        <div v-for="a in automations" :key="a.id" class="px-5 py-4 flex items-center gap-4 border-b last:border-0" style="border-color:#eef0f2;">
            <span class="px-2.5 py-1 rounded-full text-xs font-semibold"
                :style="a.active ? 'background:#eaf7f1; color:#268866;' : 'background:#eef0f2; color:#72777d;'">
                {{ a.active ? 'Active' : 'Paused' }}
            </span>
            <div class="flex-1 min-w-0">
                <div class="text-sm font-semibold" style="color:#181a1c;">{{ a.name }}</div>
                <div class="text-xs mt-0.5" style="color:#72777d;">
                    When: {{ triggerLabel(a.trigger) }} → Then: {{ actionLabel(a.action) }} · {{ configSummary(a) }}
                </div>
                <div class="text-xs" style="color:#72777d;">Last run: {{ fmtDateTime(a.last_run_at) }}</div>
            </div>
            <button @click="startEdit(a)" class="text-xs font-semibold px-3 py-1.5 rounded-lg border" style="border-color:#e5e7e9; color:#181a1c;">
                Edit
            </button>
            <button @click="remove(a)" class="text-xs font-semibold px-3 py-1.5 rounded-lg" style="color:#c64f4f;">
                Delete
            </button>
        </div>
    </div>

    <!-- Create / edit form -->
    <div class="bg-white rounded-xl p-5" style="box-shadow:0 1px 3px rgba(0,0,0,.06);">
        <h2 class="text-base font-bold mb-4" style="color:#181a1c;">{{ editing.id ? 'Edit rule' : 'New rule' }}</h2>
        <form @submit.prevent="submit" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold mb-1" style="color:#72777d;">Rule name</label>
                <input v-model="form.name" type="text" placeholder="e.g. Follow up after 3 silent days"
                    class="w-full rounded-lg border px-3 py-2 text-sm" style="border-color:#e5e7e9;" />
            </div>
            <div class="flex items-end gap-2">
                <label class="flex items-center gap-2 text-sm font-medium cursor-pointer" style="color:#181a1c;">
                    <input type="checkbox" v-model="form.active" class="w-4 h-4 accent-[#f26a2e]" /> Active
                </label>
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1" style="color:#72777d;">Trigger</label>
                <select v-model="form.trigger" class="w-full rounded-lg border px-3 py-2 text-sm bg-white" style="border-color:#e5e7e9;">
                    <option v-for="t in triggers" :key="t.key" :value="t.key">{{ t.label }}</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1" style="color:#72777d;">Action</label>
                <select v-model="form.action" class="w-full rounded-lg border px-3 py-2 text-sm bg-white" style="border-color:#e5e7e9;">
                    <option v-for="a in actions" :key="a.key" :value="a.key">{{ a.label }}</option>
                </select>
            </div>

            <!-- Config fields -->
            <div v-if="form.trigger === 'no_reply_days'">
                <label class="block text-xs font-semibold mb-1" style="color:#72777d;">Days with no reply</label>
                <input v-model.number="form.config.days" type="number" min="1"
                    class="w-full rounded-lg border px-3 py-2 text-sm" style="border-color:#e5e7e9;" />
            </div>
            <div v-if="form.trigger === 'stage_changed'">
                <label class="block text-xs font-semibold mb-1" style="color:#72777d;">Only when new stage is (optional)</label>
                <select v-model="form.config.stage" class="w-full rounded-lg border px-3 py-2 text-sm bg-white" style="border-color:#e5e7e9;">
                    <option value="">Any stage change</option>
                    <option v-for="s in stageOptions" :key="s" :value="s">{{ s }}</option>
                </select>
            </div>
            <div v-if="form.action === 'create_task'">
                <label class="block text-xs font-semibold mb-1" style="color:#72777d;">Task title</label>
                <input v-model="form.config.task_title" type="text"
                    class="w-full rounded-lg border px-3 py-2 text-sm" style="border-color:#e5e7e9;" />
            </div>
            <div v-if="form.action === 'send_email'">
                <label class="block text-xs font-semibold mb-1" style="color:#72777d;">Email template</label>
                <select v-model="form.config.template_id" class="w-full rounded-lg border px-3 py-2 text-sm bg-white" style="border-color:#e5e7e9;">
                    <option value="">Select template</option>
                    <option v-for="t in emailTemplates" :key="t.id" :value="t.id">{{ t.name }}</option>
                </select>
            </div>
            <div v-if="form.action === 'change_stage'">
                <label class="block text-xs font-semibold mb-1" style="color:#72777d;">Move to stage</label>
                <select v-model="form.config.stage" class="w-full rounded-lg border px-3 py-2 text-sm bg-white" style="border-color:#e5e7e9;">
                    <option v-for="s in stageOptions" :key="s" :value="s">{{ s }}</option>
                </select>
            </div>

            <div class="md:col-span-2 flex gap-2">
                <button type="submit" :disabled="form.processing || !form.name.trim()"
                    class="rounded-lg px-4 py-2 text-sm font-semibold text-white disabled:opacity-50" style="background:#f26a2e;">
                    {{ editing.id ? 'Save changes' : 'Create rule' }}
                </button>
                <button v-if="editing.id" type="button" @click="startCreate"
                    class="rounded-lg px-4 py-2 text-sm font-semibold border" style="border-color:#e5e7e9; color:#181a1c;">
                    Cancel
                </button>
            </div>
            <p v-if="form.errors.name" class="text-xs md:col-span-2" style="color:#c64f4f;">{{ form.errors.name }}</p>
        </form>
    </div>
</template>
