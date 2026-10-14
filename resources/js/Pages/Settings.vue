<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    profile: Object,
    services: Array,
    integration_keys: Array,
    configured: Object,
    masked: Object,
});

const profileForm = useForm({
    company_name: props.profile.company_name || '',
    company_email: props.profile.company_email || '',
    company_phone: props.profile.company_phone || '',
});

function saveProfile() {
    profileForm.post(route('settings.update'), { preserveScroll: true });
}

const secretForm = useForm(
    Object.fromEntries(props.integration_keys.map((k) => [k, '']))
);

function saveSecrets() {
    secretForm.post(route('settings.update'), {
        preserveScroll: true,
        onSuccess: () => secretForm.reset(),
    });
}

const newService = useForm({ name: '', description: '', price: 0, active: true });
function addService() {
    newService.post(route('services.store'), {
        preserveScroll: true,
        onSuccess: () => newService.reset(),
    });
}

const editingId = ref(null);
const editForm = useForm({ name: '', description: '', price: 0, active: true });
function startEdit(s) {
    editingId.value = s.id;
    editForm.name = s.name;
    editForm.description = s.description || '';
    editForm.price = Number(s.price);
    editForm.active = !!s.active;
}
function cancelEdit() {
    editingId.value = null;
    editForm.reset();
}
function saveEdit(id) {
    editForm.put(route('services.update', id), {
        preserveScroll: true,
        onSuccess: () => { editingId.value = null; },
    });
}
function deleteService(id) {
    if (confirm('Delete this service?')) {
        router.delete(route('services.destroy', id), { preserveScroll: true });
    }
}

const pkr = (n) => 'PKR ' + Number(n || 0).toLocaleString('en-PK');
const inputCls = 'w-full px-3 py-2 rounded-lg border text-sm';
const inputStyle = 'border-color:#e5e7e9;';
const cardStyle = 'box-shadow:0 1px 4px rgba(0,0,0,.05);';
</script>

<template>
    <Head title="Settings" />

    <h1 class="text-2xl font-bold mb-6" style="color:#181a1c;">Settings</h1>

    <!-- Business profile -->
    <div class="bg-white rounded-xl p-6 mb-6" :style="cardStyle">
        <h2 class="font-semibold mb-1" style="color:#181a1c;">Business profile</h2>
        <p class="text-sm mb-4" style="color:#72777d;">Shown on proposals and client-facing pages.</p>
        <form @submit.prevent="saveProfile" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-semibold mb-1" style="color:#72777d;">Company name</label>
                <input v-model="profileForm.company_name" type="text" :class="inputCls" :style="inputStyle" />
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1" style="color:#72777d;">Email</label>
                <input v-model="profileForm.company_email" type="email" :class="inputCls" :style="inputStyle" />
            </div>
            <div>
                <label class="block text-xs font-semibold mb-1" style="color:#72777d;">Phone</label>
                <input v-model="profileForm.company_phone" type="text" :class="inputCls" :style="inputStyle" />
            </div>
            <div class="md:col-span-3">
                <button type="submit" :disabled="profileForm.processing"
                    class="px-4 py-2 rounded-lg text-sm font-semibold text-white" style="background:#f26a2e;">
                    {{ profileForm.processing ? 'Saving…' : 'Save profile' }}
                </button>
            </div>
        </form>
    </div>

    <!-- Services catalog -->
    <div class="bg-white rounded-xl p-6 mb-6" :style="cardStyle">
        <h2 class="font-semibold mb-1" style="color:#181a1c;">Services catalog</h2>
        <p class="text-sm mb-4" style="color:#72777d;">Sellable items you can add to proposals. Prices in PKR.</p>

        <form @submit.prevent="addService" class="grid grid-cols-12 gap-2 mb-5 items-end p-4 rounded-lg" style="background:#f7f8f9;">
            <div class="col-span-12 md:col-span-3">
                <label class="block text-xs font-semibold mb-1" style="color:#72777d;">Name *</label>
                <input v-model="newService.name" type="text" :class="inputCls" :style="inputStyle" required />
            </div>
            <div class="col-span-12 md:col-span-4">
                <label class="block text-xs font-semibold mb-1" style="color:#72777d;">Description</label>
                <input v-model="newService.description" type="text" :class="inputCls" :style="inputStyle" />
            </div>
            <div class="col-span-6 md:col-span-2">
                <label class="block text-xs font-semibold mb-1" style="color:#72777d;">Price (PKR) *</label>
                <input v-model.number="newService.price" type="number" min="0" :class="inputCls" :style="inputStyle" required />
            </div>
            <div class="col-span-4 md:col-span-2 flex items-center gap-2 pb-2">
                <input v-model="newService.active" type="checkbox" id="ns-active" class="w-4 h-4" />
                <label for="ns-active" class="text-sm" style="color:#72777d;">Active</label>
            </div>
            <div class="col-span-2 md:col-span-1">
                <button type="submit" :disabled="newService.processing" class="w-full px-3 py-2 rounded-lg text-sm font-semibold text-white" style="background:#f26a2e;">Add</button>
            </div>
        </form>

        <table class="w-full text-sm">
            <thead>
                <tr class="text-left" style="color:#72777d; border-bottom:1px solid #e5e7e9;">
                    <th class="py-2 font-medium">Name</th>
                    <th class="py-2 font-medium">Description</th>
                    <th class="py-2 font-medium text-right">Price</th>
                    <th class="py-2 font-medium">Status</th>
                    <th class="py-2 font-medium text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <template v-for="s in services" :key="s.id">
                    <tr v-if="editingId !== s.id" style="border-bottom:1px solid #eef0f2;">
                        <td class="py-2.5 font-semibold" style="color:#181a1c;">{{ s.name }}</td>
                        <td class="py-2.5" style="color:#72777d;">{{ s.description || '—' }}</td>
                        <td class="py-2.5 text-right font-semibold">{{ pkr(s.price) }}</td>
                        <td class="py-2.5">
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold"
                                :style="s.active ? 'background:#eaf7f1; color:#268866;' : 'background:#eef0f2; color:#72777d;'">
                                {{ s.active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="py-2.5 text-right">
                            <button @click="startEdit(s)" class="text-xs font-semibold mr-3" style="color:#3178c6;">Edit</button>
                            <button @click="deleteService(s.id)" class="text-xs font-semibold" style="color:#c64f4f;">Delete</button>
                        </td>
                    </tr>
                    <tr v-else style="border-bottom:1px solid #eef0f2; background:#fff8f3;">
                        <td class="py-2.5"><input v-model="editForm.name" type="text" :class="inputCls" :style="inputStyle" /></td>
                        <td class="py-2.5"><input v-model="editForm.description" type="text" :class="inputCls" :style="inputStyle" /></td>
                        <td class="py-2.5"><input v-model.number="editForm.price" type="number" min="0" :class="inputCls" :style="inputStyle" /></td>
                        <td class="py-2.5">
                            <label class="flex items-center gap-1.5 text-xs" style="color:#72777d;">
                                <input v-model="editForm.active" type="checkbox" class="w-4 h-4" /> Active
                            </label>
                        </td>
                        <td class="py-2.5 text-right whitespace-nowrap">
                            <button @click="saveEdit(s.id)" class="text-xs font-semibold mr-3" style="color:#268866;">Save</button>
                            <button @click="cancelEdit" class="text-xs font-semibold" style="color:#72777d;">Cancel</button>
                        </td>
                    </tr>
                </template>
                <tr v-if="!services.length">
                    <td colspan="5" class="py-8 text-center" style="color:#72777d;">No services yet. Add your first one above.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Integrations -->
    <div class="bg-white rounded-xl p-6 mb-6" :style="cardStyle">
        <h2 class="font-semibold mb-1" style="color:#181a1c;">Integrations</h2>
        <p class="text-sm mb-4" style="color:#72777d;">Secrets are stored securely and never shown back — only a masked preview. Leave a field blank to keep its current value.</p>
        <form @submit.prevent="saveSecrets" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div v-for="key in integration_keys" :key="key">
                <div class="flex items-center justify-between mb-1">
                    <label class="text-xs font-semibold" style="color:#72777d;">{{ key }}</label>
                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold"
                        :style="configured[key] ? 'background:#eaf7f1; color:#268866;' : 'background:#eef0f2; color:#72777d;'">
                        {{ configured[key] ? 'Configured' : 'Not configured' }}
                    </span>
                </div>
                <input v-model="secretForm[key]" :type="key === 'GOOGLE_CREDENTIALS_JSON' ? 'text' : 'password'"
                    :placeholder="configured[key] ? (masked[key] || '••••••••') : 'Not set'"
                    :class="inputCls" :style="inputStyle" autocomplete="off" />
            </div>
            <div class="md:col-span-2">
                <button type="submit" :disabled="secretForm.processing"
                    class="px-4 py-2 rounded-lg text-sm font-semibold text-white" style="background:#f26a2e;">
                    {{ secretForm.processing ? 'Saving…' : 'Save integrations' }}
                </button>
            </div>
        </form>
    </div>
</template>
