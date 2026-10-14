<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    proposal: Object,
    leads: Array,
    services: Array,
});

const form = useForm({
    lead_id: props.proposal.lead_id,
    title: props.proposal.title,
    valid_until: props.proposal.valid_until || '',
    notes: props.proposal.notes || '',
    discount: Number(props.proposal.discount) || 0,
    items: props.proposal.items.map((i) => ({
        service_id: i.service_id || '',
        name: i.name,
        qty: i.qty,
        price: Number(i.price),
    })),
});

const subtotal = computed(() =>
    form.items.reduce((s, i) => s + Number(i.qty || 0) * Number(i.price || 0), 0)
);
const discount = computed(() => Math.min(Number(form.discount || 0), subtotal.value));
const total = computed(() => subtotal.value - discount.value);

const pkr = (n) => 'PKR ' + Number(n || 0).toLocaleString('en-PK');

function onServiceChange(item) {
    const svc = props.services.find((s) => String(s.id) === String(item.service_id));
    if (svc) {
        item.name = svc.name;
        item.price = Number(svc.price);
    }
}

function addRow() {
    form.items.push({ service_id: '', name: '', qty: 1, price: 0 });
}

function removeRow(i) {
    if (form.items.length > 1) form.items.splice(i, 1);
}

function submit() {
    form.put(route('proposals.update', props.proposal.id));
}

const inputCls = 'w-full px-3 py-2 rounded-lg border text-sm';
const inputStyle = 'border-color:#e5e7e9;';
</script>

<template>
    <Head title="Edit Proposal" />

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold" style="color:#181a1c;">Edit Proposal</h1>
        <Link :href="route('proposals.show', proposal.id)" class="text-sm font-medium" style="color:#72777d;">← Back to proposal</Link>
    </div>

    <form @submit.prevent="submit" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-xl p-6" style="box-shadow:0 1px 4px rgba(0,0,0,.05);">
                <h2 class="font-semibold mb-4" style="color:#181a1c;">Details</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold mb-1" style="color:#72777d;">Lead *</label>
                        <select v-model="form.lead_id" :class="inputCls" :style="inputStyle" required>
                            <option value="" disabled>Select a lead</option>
                            <option v-for="l in leads" :key="l.id" :value="l.id">
                                {{ l.business_name }}{{ l.city ? ' — ' + l.city : '' }}
                            </option>
                        </select>
                        <div v-if="form.errors.lead_id" class="text-xs mt-1" style="color:#c64f4f;">{{ form.errors.lead_id }}</div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1" style="color:#72777d;">Title *</label>
                        <input v-model="form.title" type="text" :class="inputCls" :style="inputStyle" required />
                        <div v-if="form.errors.title" class="text-xs mt-1" style="color:#c64f4f;">{{ form.errors.title }}</div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1" style="color:#72777d;">Valid until</label>
                        <input v-model="form.valid_until" type="date" :class="inputCls" :style="inputStyle" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1" style="color:#72777d;">Discount (PKR)</label>
                        <input v-model="form.discount" type="number" min="0" :class="inputCls" :style="inputStyle" />
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold mb-1" style="color:#72777d;">Notes</label>
                        <textarea v-model="form.notes" rows="3" :class="inputCls" :style="inputStyle"></textarea>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl p-6" style="box-shadow:0 1px 4px rgba(0,0,0,.05);">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-semibold" style="color:#181a1c;">Items</h2>
                    <button type="button" @click="addRow" class="px-3 py-1.5 rounded-lg text-xs font-semibold border"
                        style="border-color:#e5e7e9; color:#72777d;">+ Add item</button>
                </div>
                <div v-for="(item, i) in form.items" :key="i" class="grid grid-cols-12 gap-2 mb-3 items-end">
                    <div class="col-span-12 md:col-span-4">
                        <label class="block text-xs font-semibold mb-1" style="color:#72777d;">Service</label>
                        <select v-model="item.service_id" @change="onServiceChange(item)" :class="inputCls" :style="inputStyle">
                            <option value="">Custom item</option>
                            <option v-for="s in services" :key="s.id" :value="s.id">{{ s.name }} — PKR {{ Number(s.price).toLocaleString() }}</option>
                        </select>
                    </div>
                    <div class="col-span-12 md:col-span-3">
                        <label class="block text-xs font-semibold mb-1" style="color:#72777d;">Name *</label>
                        <input v-model="item.name" type="text" :class="inputCls" :style="inputStyle" required />
                    </div>
                    <div class="col-span-4 md:col-span-1">
                        <label class="block text-xs font-semibold mb-1" style="color:#72777d;">Qty</label>
                        <input v-model.number="item.qty" type="number" min="1" :class="inputCls" :style="inputStyle" />
                    </div>
                    <div class="col-span-4 md:col-span-2">
                        <label class="block text-xs font-semibold mb-1" style="color:#72777d;">Price (PKR)</label>
                        <input v-model.number="item.price" type="number" min="0" :class="inputCls" :style="inputStyle" />
                    </div>
                    <div class="col-span-3 md:col-span-1 text-right">
                        <div class="text-sm font-semibold mb-2" style="color:#181a1c;">{{ pkr(item.qty * item.price) }}</div>
                    </div>
                    <div class="col-span-1 text-right">
                        <button type="button" @click="removeRow(i)" class="text-xs font-semibold" style="color:#c64f4f;">✕</button>
                    </div>
                </div>
                <div v-if="form.errors.items" class="text-xs mt-1" style="color:#c64f4f;">{{ form.errors.items }}</div>
            </div>
        </div>

        <div>
            <div class="bg-white rounded-xl p-6 sticky top-6" style="box-shadow:0 1px 4px rgba(0,0,0,.05);">
                <h2 class="font-semibold mb-4" style="color:#181a1c;">Summary</h2>
                <div class="flex justify-between text-sm py-1" style="color:#72777d;"><span>Subtotal</span><span>{{ pkr(subtotal) }}</span></div>
                <div class="flex justify-between text-sm py-1" style="color:#72777d;"><span>Discount</span><span>− {{ pkr(discount) }}</span></div>
                <div class="flex justify-between text-lg font-bold py-2 mt-2" style="color:#181a1c; border-top:2px solid #f26a2e;"><span>Total</span><span>{{ pkr(total) }}</span></div>
                <button type="submit" :disabled="form.processing"
                    class="w-full mt-4 px-4 py-2.5 rounded-lg text-sm font-semibold text-white"
                    style="background:#f26a2e;" :style="form.processing ? 'opacity:.6;' : ''">
                    {{ form.processing ? 'Saving…' : 'Save changes' }}
                </button>
            </div>
        </div>
    </form>
</template>
