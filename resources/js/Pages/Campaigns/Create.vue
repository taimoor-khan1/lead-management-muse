<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineOptions({ layout: AppLayout });

defineProps({ leads: Array, templates: Array });

const form = useForm({
    name: '',
    channel: 'email',
    email_template_id: '',
    whatsapp_template: '',
    lead_ids: [],
});

const search = ref('');
const allChecked = ref(false);

const filteredLeads = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return props.leads;
    return props.leads.filter((l) =>
        [l.business_name, l.city, l.industry, l.contact_person]
            .filter(Boolean).join(' ').toLowerCase().includes(q)
    );
});

const toggleAll = () => {
    form.lead_ids = allChecked.value ? filteredLeads.value.map((l) => l.id) : [];
};

const submit = () => {
    form.post(route('campaigns.store'));
};

const inputCls = 'w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2';
const ringStyle = 'border-color:#e5e7e9;color:#181a1c;--tw-ring-color:#f26a2e;';
</script>

<template>
    <Head title="New Campaign" />

    <div class="mb-6">
        <h1 class="text-2xl font-bold" style="color:#181a1c;">New Campaign</h1>
        <p class="text-sm mt-1" style="color:#72777d;">Pick a channel, a message, and the leads to reach.</p>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl border shadow-sm p-6" style="border-color:#e5e7e9;">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold mb-1" style="color:#181a1c;">Campaign name</label>
                    <input v-model="form.name" type="text" :class="inputCls" :style="ringStyle" placeholder="e.g. October Website Outreach" />
                    <p v-if="form.errors.name" class="text-xs mt-1" style="color:#c64f4f;">{{ form.errors.name }}</p>
                </div>

                <div>
                    <label class="block text-sm font-semibold mb-2" style="color:#181a1c;">Channel</label>
                    <div class="flex gap-2">
                        <button type="button" @click="form.channel = 'email'" class="px-4 py-2 rounded-lg text-sm font-semibold border"
                            :style="form.channel === 'email' ? 'background:#f26a2e;border-color:#f26a2e;color:#fff;' : 'border-color:#e5e7e9;color:#72777d;'">
                            Email
                        </button>
                        <button type="button" @click="form.channel = 'whatsapp'" class="px-4 py-2 rounded-lg text-sm font-semibold border"
                            :style="form.channel === 'whatsapp' ? 'background:#f26a2e;border-color:#f26a2e;color:#fff;' : 'border-color:#e5e7e9;color:#72777d;'">
                            WhatsApp
                        </button>
                    </div>
                    <p v-if="form.errors.channel" class="text-xs mt-1" style="color:#c64f4f;">{{ form.errors.channel }}</p>
                </div>

                <div v-if="form.channel === 'email'">
                    <label class="block text-sm font-semibold mb-1" style="color:#181a1c;">Email template</label>
                    <select v-model="form.email_template_id" :class="inputCls" :style="ringStyle">
                        <option value="" disabled>Select a template…</option>
                        <option v-for="t in templates" :key="t.id" :value="t.id">{{ t.name }}</option>
                    </select>
                    <p v-if="form.errors.email_template_id" class="text-xs mt-1" style="color:#c64f4f;">{{ form.errors.email_template_id }}</p>
                </div>

                <div v-else>
                    <label class="block text-sm font-semibold mb-1" style="color:#181a1c;">WhatsApp message</label>
                    <textarea v-model="form.whatsapp_template" rows="6" :class="inputCls" :style="ringStyle"
                        placeholder="Hi {{contact_person}}, quick idea for {{business_name}}…"></textarea>
                    <p class="text-xs mt-1" style="color:#72777d;">Variables: {{business_name}}, {{contact_person}}, {{city}}, {{industry}}, {{suggested_service}}</p>
                    <p v-if="form.errors.whatsapp_template" class="text-xs mt-1" style="color:#c64f4f;">{{ form.errors.whatsapp_template }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border shadow-sm p-6" style="border-color:#e5e7e9;">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-semibold" style="color:#181a1c;">
                    Select leads <span class="font-normal" style="color:#72777d;">({{ form.lead_ids.length }} selected)</span>
                </h2>
                <label class="flex items-center gap-2 text-sm" style="color:#72777d;">
                    <input type="checkbox" v-model="allChecked" @change="toggleAll" class="rounded" style="accent-color:#f26a2e;" />
                    Select all
                </label>
            </div>
            <input v-model="search" type="text" placeholder="Search leads…" :class="inputCls + ' mb-3'" :style="ringStyle" />
            <p v-if="form.errors.lead_ids" class="text-xs mb-2" style="color:#c64f4f;">{{ form.errors.lead_ids }}</p>

            <div class="max-h-[320px] overflow-y-auto rounded-lg border" style="border-color:#e5e7e9;">
                <label v-for="l in filteredLeads" :key="l.id"
                    class="flex items-center gap-3 px-4 py-2.5 border-b last:border-0 cursor-pointer hover:bg-gray-50" style="border-color:#e5e7e9;">
                    <input type="checkbox" :value="l.id" v-model="form.lead_ids" class="rounded" style="accent-color:#f26a2e;" />
                    <div class="min-w-0">
                        <div class="text-sm font-semibold truncate" style="color:#181a1c;">{{ l.business_name }}</div>
                        <div class="text-xs truncate" style="color:#72777d;">
                            {{ [l.city, l.industry].filter(Boolean).join(' · ') }}
                            <span v-if="form.channel === 'email' && !l.email" style="color:#c64f4f;"> — no email</span>
                            <span v-if="form.channel === 'whatsapp' && !l.phone" style="color:#c64f4f;"> — no phone</span>
                        </div>
                    </div>
                </label>
                <p v-if="!filteredLeads.length" class="px-4 py-8 text-center text-sm" style="color:#72777d;">No leads match your search.</p>
            </div>

            <div class="flex items-center gap-3 pt-4">
                <button @click="submit" :disabled="form.processing" class="px-5 py-2 rounded-lg text-sm font-semibold text-white disabled:opacity-60" style="background:#f26a2e;">
                    Create Campaign
                </button>
                <Link :href="route('campaigns.index')" class="text-sm font-medium" style="color:#72777d;">Cancel</Link>
            </div>
        </div>
    </div>
</template>
