<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineOptions({ layout: AppLayout });

const props = defineProps({ lead: Object, leadNotes: { type: Array, default: () => [] } });

const flash = computed(() => usePage().props.flash || {});
const tab = ref('notes');

const noteForm = useForm({ body: '' });
const taskForm = useForm({ title: '', due_date: '', lead_id: props.lead.id });

function addNote() {
    noteForm.post(route('leads.notes.store', props.lead.id), {
        preserveScroll: true,
        onSuccess: () => noteForm.reset(),
    });
}

function deleteNote(id) {
    if (confirm('Delete this note?')) {
        router.delete(route('leads.notes.destroy', id), { preserveScroll: true });
    }
}

function addTask() {
    taskForm.post(route('tasks.store'), {
        preserveScroll: true,
        onSuccess: () => taskForm.reset('title', 'due_date'),
    });
}

function toggleTask(id) {
    router.post(route('tasks.toggle', id), {}, { preserveScroll: true });
}

function deleteTask(id) {
    if (confirm('Delete this task?')) {
        router.delete(route('tasks.destroy', id), { preserveScroll: true });
    }
}

function deleteLead() {
    if (confirm(`Delete "${props.lead.business_name}"? This cannot be undone.`)) {
        router.delete(route('leads.destroy', props.lead.id));
    }
}

function stagePill(stage) {
    const map = {
        new: 'color:#3178c6;background:#edf5ff;',
        contacted: 'color:#ad6c14;background:#fff6dc;',
        replied: 'color:#268866;background:#eaf7f1;',
        proposal: 'color:#3178c6;background:#edf5ff;',
        won: 'color:#268866;background:#eaf7f1;',
        lost: 'color:#c64f4f;background:#fff0f0;',
    };
    return map[stage] || 'color:#72777d;background:#eef0f2;';
}

function websitePill(status) {
    const map = {
        none: 'color:#c64f4f;background:#fff0f0;',
        weak: 'color:#ad6c14;background:#fff6dc;',
        ok: 'color:#268866;background:#eaf7f1;',
    };
    return map[status] || 'color:#72777d;background:#eef0f2;';
}

function statusPill(status) {
    const s = (status || '').toLowerCase();
    if (['sent', 'accepted', 'completed', 'done', 'opened', 'clicked'].includes(s)) return 'color:#268866;background:#eaf7f1;';
    if (['failed', 'declined'].includes(s)) return 'color:#c64f4f;background:#fff0f0;';
    if (['queued', 'pending', 'draft', 'sending', 'scheduled'].includes(s)) return 'color:#ad6c14;background:#fff6dc;';
    return 'color:#3178c6;background:#edf5ff;';
}

function fmtDate(v) {
    if (!v) return '—';
    return new Date(v).toLocaleString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
}

const tabs = [
    { id: 'notes', label: 'Notes' },
    { id: 'tasks', label: 'Tasks' },
    { id: 'email', label: 'Email History' },
    { id: 'whatsapp', label: 'WhatsApp' },
    { id: 'proposals', label: 'Proposals' },
];

const cardStyle = 'background:#fff;border:1px solid #e5e7e9;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,.05);';
const inputCls = 'rounded-lg border text-sm px-3 py-2 w-full focus:outline-none focus:ring-2 focus:ring-orange-200';
const inputStyle = 'border-color:#e5e7e9;color:#181a1c;';
const thStyle = 'color:#72777d;';
</script>

<template>
    <Head :title="lead.business_name" />

    <div class="p-6 max-w-[1100px] mx-auto">
        <div class="flex items-center justify-between mb-5 flex-wrap gap-3">
            <Link :href="route('leads.index')" class="text-sm font-semibold" style="color:#72777d;">← Back to leads</Link>
            <div class="flex gap-2">
                <Link :href="route('leads.edit', lead.id)" class="px-4 py-2 rounded-lg text-sm font-semibold border"
                    style="border-color:#e5e7e9;color:#181a1c;background:#fff;">Edit</Link>
                <button @click="deleteLead" class="px-4 py-2 rounded-lg text-sm font-semibold border"
                    style="border-color:#f3d2d2;color:#c64f4f;background:#fff;">Delete</button>
            </div>
        </div>

        <div v-if="flash.message" class="mb-4 px-4 py-3 rounded-lg text-sm"
            style="background:#eaf7f1;color:#268866;border:1px solid #cdeadb;">{{ flash.message }}</div>
        <div v-if="flash.error" class="mb-4 px-4 py-3 rounded-lg text-sm"
            style="background:#fff0f0;color:#c64f4f;border:1px solid #f3d2d2;">{{ flash.error }}</div>

        <div class="p-6 mb-5" :style="cardStyle">
            <div class="flex items-start justify-between flex-wrap gap-3 mb-4">
                <div>
                    <h1 class="text-2xl font-bold" style="color:#181a1c;">{{ lead.business_name }}</h1>
                    <div v-if="lead.contact_person" class="text-sm mt-1" style="color:#72777d;">{{ lead.contact_person }}</div>
                </div>
                <div class="flex gap-2">
                    <span class="px-3 py-1 rounded-full text-xs font-semibold capitalize" :style="stagePill(lead.stage)">{{ lead.stage }}</span>
                    <span class="px-3 py-1 rounded-full text-xs font-semibold capitalize" :style="websitePill(lead.website_status)">website: {{ lead.website_status }}</span>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-x-8 gap-y-3 text-sm">
                <div><span class="font-semibold" style="color:#72777d;">Phone:</span> <span style="color:#181a1c;">{{ lead.phone || '—' }}</span></div>
                <div><span class="font-semibold" style="color:#72777d;">Email:</span> <span style="color:#181a1c;">{{ lead.email || '—' }}</span></div>
                <div><span class="font-semibold" style="color:#72777d;">Website:</span>
                    <a v-if="lead.website" :href="lead.website" target="_blank" class="hover:underline" style="color:#3178c6;">{{ lead.website }}</a>
                    <span v-else style="color:#181a1c;">—</span>
                </div>
                <div><span class="font-semibold" style="color:#72777d;">City:</span> <span style="color:#181a1c;">{{ lead.city || '—' }}</span></div>
                <div><span class="font-semibold" style="color:#72777d;">Industry:</span> <span style="color:#181a1c;">{{ lead.industry || '—' }}</span></div>
                <div><span class="font-semibold" style="color:#72777d;">Suggested Service:</span> <span style="color:#181a1c;">{{ lead.suggested_service || '—' }}</span></div>
                <div><span class="font-semibold" style="color:#72777d;">Address:</span> <span style="color:#181a1c;">{{ lead.address || '—' }}</span></div>
                <div><span class="font-semibold" style="color:#72777d;">Location:</span>
                    <span style="color:#181a1c;">{{ lead.latitude && lead.longitude ? `${lead.latitude}, ${lead.longitude}` : '—' }}</span>
                </div>
                <div><span class="font-semibold" style="color:#72777d;">Owner:</span> <span style="color:#181a1c;">{{ lead.owner?.name || '—' }}</span></div>
                <div><span class="font-semibold" style="color:#72777d;">Source:</span> <span style="color:#181a1c;">{{ lead.source || '—' }}</span></div>
                <div><span class="font-semibold" style="color:#72777d;">Score:</span> <span style="color:#181a1c;">{{ lead.score ?? 0 }}</span></div>
                <div><span class="font-semibold" style="color:#72777d;">Last contacted:</span> <span style="color:#181a1c;">{{ fmtDate(lead.last_contacted_at) }}</span></div>
            </div>

            <div v-if="lead.notes" class="mt-4 pt-4 border-t text-sm" style="border-color:#e5e7e9;">
                <span class="font-semibold" style="color:#72777d;">Notes:</span>
                <p class="mt-1 whitespace-pre-line" style="color:#181a1c;">{{ lead.notes }}</p>
            </div>
        </div>

        <div class="flex gap-1 mb-4 border-b" style="border-color:#e5e7e9;">
            <button v-for="t in tabs" :key="t.id" @click="tab = t.id"
                class="px-4 py-2.5 text-sm font-semibold border-b-2 -mb-px"
                :style="tab === t.id ? 'color:#a73c10;border-color:#f26a2e;' : 'color:#72777d;border-color:transparent;'">
                {{ t.label }}
                <span v-if="t.id === 'notes'" class="ml-1 text-xs opacity-70">({{ leadNotes.length }})</span>
                <span v-if="t.id === 'tasks'" class="ml-1 text-xs opacity-70">({{ lead.tasks?.length ?? 0 }})</span>
            </button>
        </div>

        <!-- NOTES -->
        <div v-if="tab === 'notes'" class="p-5" :style="cardStyle">
            <form @submit.prevent="addNote" class="flex gap-2 mb-4">
                <input v-model="noteForm.body" type="text" placeholder="Add a note…" :class="inputCls" :style="inputStyle" />
                <button type="submit" :disabled="noteForm.processing || !noteForm.body.trim()"
                    class="px-4 py-2 rounded-lg text-sm font-semibold text-white disabled:opacity-50 shrink-0"
                    style="background:#f26a2e;">Add</button>
            </form>
            <div v-if="noteForm.errors.body" class="text-xs mb-2" style="color:#c64f4f;">{{ noteForm.errors.body }}</div>
            <div v-if="leadNotes.length === 0" class="text-sm py-4 text-center" style="color:#72777d;">No notes yet.</div>
            <div v-for="note in leadNotes" :key="note.id" class="py-3 border-b last:border-0 flex justify-between gap-3" style="border-color:#f0f1f2;">
                <div>
                    <p class="text-sm whitespace-pre-line" style="color:#181a1c;">{{ note.body }}</p>
                    <div class="text-xs mt-1" style="color:#72777d;">{{ note.user?.name || '—' }} · {{ fmtDate(note.created_at) }}</div>
                </div>
                <button @click="deleteNote(note.id)" class="text-xs font-semibold shrink-0" style="color:#c64f4f;">Delete</button>
            </div>
        </div>

        <!-- TASKS -->
        <div v-if="tab === 'tasks'" class="p-5" :style="cardStyle">
            <form @submit.prevent="addTask" class="flex gap-2 mb-4 flex-wrap">
                <input v-model="taskForm.title" type="text" placeholder="New task…" :class="inputCls + ' flex-1 min-w-[200px]'" :style="inputStyle" />
                <input v-model="taskForm.due_date" type="date" :class="inputCls + ' w-auto'" :style="inputStyle" />
                <button type="submit" :disabled="taskForm.processing || !taskForm.title.trim()"
                    class="px-4 py-2 rounded-lg text-sm font-semibold text-white disabled:opacity-50 shrink-0"
                    style="background:#f26a2e;">Add</button>
            </form>
            <div v-if="!lead.tasks || lead.tasks.length === 0" class="text-sm py-4 text-center" style="color:#72777d;">No tasks yet.</div>
            <div v-for="task in lead.tasks" :key="task.id" class="py-3 border-b last:border-0 flex items-center justify-between gap-3" style="border-color:#f0f1f2;">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" :checked="task.done" @change="toggleTask(task.id)" class="rounded" style="accent-color:#f26a2e;" />
                    <span class="text-sm" :style="task.done ? 'color:#72777d;text-decoration:line-through;' : 'color:#181a1c;'">{{ task.title }}</span>
                    <span v-if="task.due_date" class="text-xs" style="color:#72777d;">due {{ task.due_date }}</span>
                </label>
                <button @click="deleteTask(task.id)" class="text-xs font-semibold shrink-0" style="color:#c64f4f;">Delete</button>
            </div>
        </div>

        <!-- EMAIL HISTORY -->
        <div v-if="tab === 'email'" class="overflow-x-auto" :style="cardStyle">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left border-b" style="border-color:#e5e7e9;">
                        <th class="px-4 py-3 font-semibold" :style="thStyle">Subject</th>
                        <th class="px-4 py-3 font-semibold" :style="thStyle">Template</th>
                        <th class="px-4 py-3 font-semibold" :style="thStyle">Status</th>
                        <th class="px-4 py-3 font-semibold" :style="thStyle">Sent At</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="e in lead.email_sends" :key="e.id" class="border-b last:border-0" style="border-color:#f0f1f2;">
                        <td class="px-4 py-3" style="color:#181a1c;">{{ e.subject }}</td>
                        <td class="px-4 py-3" style="color:#72777d;">{{ e.template?.name || '—' }}</td>
                        <td class="px-4 py-3"><span class="px-2.5 py-1 rounded-full text-xs font-semibold capitalize" :style="statusPill(e.status)">{{ e.status }}</span></td>
                        <td class="px-4 py-3" style="color:#72777d;">{{ fmtDate(e.sent_at) }}</td>
                    </tr>
                    <tr v-if="!lead.email_sends || lead.email_sends.length === 0">
                        <td colspan="4" class="px-4 py-8 text-center" style="color:#72777d;">No emails sent yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- WHATSAPP HISTORY -->
        <div v-if="tab === 'whatsapp'" class="overflow-x-auto" :style="cardStyle">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left border-b" style="border-color:#e5e7e9;">
                        <th class="px-4 py-3 font-semibold" :style="thStyle">Direction</th>
                        <th class="px-4 py-3 font-semibold" :style="thStyle">Message</th>
                        <th class="px-4 py-3 font-semibold" :style="thStyle">Status</th>
                        <th class="px-4 py-3 font-semibold" :style="thStyle">At</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="m in lead.whatsapp_messages" :key="m.id" class="border-b last:border-0" style="border-color:#f0f1f2;">
                        <td class="px-4 py-3"><span class="px-2.5 py-1 rounded-full text-xs font-semibold capitalize"
                            :style="m.direction === 'in' ? 'color:#3178c6;background:#edf5ff;' : 'color:#268866;background:#eaf7f1;'">{{ m.direction }}</span></td>
                        <td class="px-4 py-3 max-w-[420px] truncate" style="color:#181a1c;" :title="m.body">{{ m.body || '—' }}</td>
                        <td class="px-4 py-3"><span class="px-2.5 py-1 rounded-full text-xs font-semibold capitalize" :style="statusPill(m.status)">{{ m.status }}</span></td>
                        <td class="px-4 py-3" style="color:#72777d;">{{ fmtDate(m.created_at) }}</td>
                    </tr>
                    <tr v-if="!lead.whatsapp_messages || lead.whatsapp_messages.length === 0">
                        <td colspan="4" class="px-4 py-8 text-center" style="color:#72777d;">No WhatsApp messages yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- PROPOSALS -->
        <div v-if="tab === 'proposals'" class="overflow-x-auto" :style="cardStyle">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left border-b" style="border-color:#e5e7e9;">
                        <th class="px-4 py-3 font-semibold" :style="thStyle">Title</th>
                        <th class="px-4 py-3 font-semibold" :style="thStyle">Total</th>
                        <th class="px-4 py-3 font-semibold" :style="thStyle">Status</th>
                        <th class="px-4 py-3 font-semibold" :style="thStyle">Created</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="p in lead.proposals" :key="p.id" class="border-b last:border-0" style="border-color:#f0f1f2;">
                        <td class="px-4 py-3">
                            <Link :href="route('proposals.show', p.id)" class="font-semibold hover:underline" style="color:#181a1c;">{{ p.title }}</Link>
                        </td>
                        <td class="px-4 py-3" style="color:#181a1c;">PKR {{ Number(p.total).toLocaleString() }}</td>
                        <td class="px-4 py-3"><span class="px-2.5 py-1 rounded-full text-xs font-semibold capitalize" :style="statusPill(p.status)">{{ p.status }}</span></td>
                        <td class="px-4 py-3" style="color:#72777d;">{{ fmtDate(p.created_at) }}</td>
                    </tr>
                    <tr v-if="!lead.proposals || lead.proposals.length === 0">
                        <td colspan="4" class="px-4 py-8 text-center" style="color:#72777d;">No proposals yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
