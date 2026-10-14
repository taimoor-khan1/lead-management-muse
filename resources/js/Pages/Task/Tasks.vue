<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    tasks: { type: Array, default: () => [] },
    filter: { type: String, default: 'all' },
    leadOptions: { type: Array, default: () => [] },
});

const form = useForm({ title: '', lead_id: '', due_date: '' });

const submit = () => form.post(route('tasks.store'), {
    onSuccess: () => form.reset(),
});

const toggle = (task) => router.post(route('tasks.toggle', task.id));
const remove = (task) => {
    if (confirm('Delete this task?')) router.delete(route('tasks.destroy', task.id));
};

const today = new Date().toISOString().slice(0, 10);
const isOverdue = (t) => !t.done && t.due_date && t.due_date < today;

const overdueTasks = computed(() => props.tasks.filter(isOverdue));
const openTasks = computed(() => props.tasks.filter((t) => !t.done && !isOverdue(t)));
const doneTasks = computed(() => props.tasks.filter((t) => t.done));

const filters = [
    { key: 'all', label: 'All' },
    { key: 'open', label: 'Open' },
    { key: 'overdue', label: 'Overdue' },
    { key: 'done', label: 'Done' },
];

const fmtDate = (d) => d ? new Date(d + 'T00:00:00').toLocaleDateString('en-PK', { day: 'numeric', month: 'short' }) : '—';
</script>

<template>
    <Head title="Tasks" />

    <div class="mb-6">
        <h1 class="text-2xl font-bold" style="color:#181a1c;">Tasks</h1>
        <p class="text-sm mt-1" style="color:#72777d;">Your follow-ups, grouped by status.</p>
    </div>

    <!-- Add form -->
    <div class="bg-white rounded-xl p-5 mb-6" style="box-shadow:0 1px 3px rgba(0,0,0,.06);">
        <form @submit.prevent="submit" class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[220px]">
                <label class="block text-xs font-semibold mb-1" style="color:#72777d;">Task title</label>
                <input v-model="form.title" type="text" placeholder="e.g. Call ABC Restaurant owner"
                    class="w-full rounded-lg border px-3 py-2 text-sm" style="border-color:#e5e7e9;" />
            </div>
            <div class="w-56">
                <label class="block text-xs font-semibold mb-1" style="color:#72777d;">Linked lead</label>
                <select v-model="form.lead_id" class="w-full rounded-lg border px-3 py-2 text-sm bg-white" style="border-color:#e5e7e9;">
                    <option value="">No lead</option>
                    <option v-for="l in leadOptions" :key="l.id" :value="l.id">{{ l.business_name }}</option>
                </select>
            </div>
            <div class="w-40">
                <label class="block text-xs font-semibold mb-1" style="color:#72777d;">Due date</label>
                <input v-model="form.due_date" type="date" class="w-full rounded-lg border px-3 py-2 text-sm" style="border-color:#e5e7e9;" />
            </div>
            <button type="submit" :disabled="form.processing || !form.title.trim()"
                class="rounded-lg px-4 py-2 text-sm font-semibold text-white disabled:opacity-50" style="background:#f26a2e;">
                Add task
            </button>
        </form>
        <p v-if="form.errors.title" class="text-xs mt-2" style="color:#c64f4f;">{{ form.errors.title }}</p>
    </div>

    <!-- Filter pills -->
    <div class="flex gap-2 mb-6">
        <Link v-for="f in filters" :key="f.key" :href="route('tasks.index', { filter: f.key })"
            class="px-3 py-1.5 rounded-full text-sm font-medium"
            :style="filter === f.key ? 'background:#f26a2e; color:#fff;' : 'background:#fff; color:#72777d;'">
            {{ f.label }}
        </Link>
    </div>

    <!-- Sections -->
    <div v-for="section in [
            { title: 'Overdue', items: overdueTasks, color: '#c64f4f' },
            { title: 'Open', items: openTasks, color: '#3178c6' },
            { title: 'Done', items: doneTasks, color: '#268866' },
        ]" :key="section.title" class="mb-8">
        <h2 class="text-sm font-bold mb-3 flex items-center gap-2" style="color:#181a1c;">
            <span class="w-2 h-2 rounded-full" :style="'background:' + section.color"></span>
            {{ section.title }} ({{ section.items.length }})
        </h2>
        <div v-if="section.items.length" class="space-y-2">
            <div v-for="task in section.items" :key="task.id"
                class="bg-white rounded-xl px-4 py-3 flex items-center gap-3" style="box-shadow:0 1px 3px rgba(0,0,0,.06);">
                <input type="checkbox" :checked="task.done" @change="toggle(task)"
                    class="w-4 h-4 rounded accent-[#f26a2e] cursor-pointer" />
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-medium" :class="task.done && 'line-through'" style="color:#181a1c;">
                        {{ task.title }}
                    </div>
                    <div class="text-xs mt-0.5" style="color:#72777d;">
                        <span v-if="task.lead">{{ task.lead.business_name }} · </span>Due {{ fmtDate(task.due_date) }}
                    </div>
                </div>
                <button @click="remove(task)" class="text-xs font-medium px-2 py-1 rounded" style="color:#c64f4f;">
                    Delete
                </button>
            </div>
        </div>
        <div v-else class="text-sm py-4 px-4 bg-white rounded-xl" style="color:#72777d; box-shadow:0 1px 3px rgba(0,0,0,.06);">
            No {{ section.title.toLowerCase() }} tasks.
        </div>
    </div>
</template>
