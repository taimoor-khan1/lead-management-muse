<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import LeadForm from './LeadForm.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineOptions({ layout: AppLayout });

const props = defineProps({ lead: Object, users: Array });

const form = useForm({
    business_name: props.lead.business_name ?? '',
    contact_person: props.lead.contact_person ?? '',
    phone: props.lead.phone ?? '',
    email: props.lead.email ?? '',
    address: props.lead.address ?? '',
    latitude: props.lead.latitude ?? '',
    longitude: props.lead.longitude ?? '',
    website: props.lead.website ?? '',
    website_status: props.lead.website_status ?? 'none',
    city: props.lead.city ?? '',
    industry: props.lead.industry ?? '',
    notes: props.lead.notes ?? '',
    suggested_service: props.lead.suggested_service ?? '',
    stage: props.lead.stage ?? 'new',
    score: props.lead.score ?? 0,
    source: props.lead.source ?? 'manual',
    owner_id: props.lead.owner_id ?? null,
});

function submit() {
    form.put(route('leads.update', props.lead.id));
}

const cardStyle = 'background:#fff;border:1px solid #e5e7e9;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,.05);';
</script>

<template>
    <Head title="Edit Lead" />

    <div class="p-6 max-w-[860px] mx-auto">
        <div class="flex items-center justify-between mb-5">
            <h1 class="text-xl font-bold" style="color:#181a1c;">Edit Lead</h1>
            <Link :href="route('leads.show', lead.id)" class="text-sm font-semibold" style="color:#72777d;">← Back to lead</Link>
        </div>

        <form @submit.prevent="submit" class="p-6" :style="cardStyle">
            <LeadForm :form="form" :users="users" />

            <div class="flex justify-end gap-2 mt-6">
                <Link :href="route('leads.show', lead.id)" class="px-4 py-2 rounded-lg text-sm font-semibold border"
                    style="border-color:#e5e7e9;color:#181a1c;background:#fff;">Cancel</Link>
                <button type="submit" :disabled="form.processing"
                    class="px-5 py-2 rounded-lg text-sm font-semibold text-white disabled:opacity-60"
                    style="background:#f26a2e;">
                    {{ form.processing ? 'Saving…' : 'Save Changes' }}
                </button>
            </div>
        </form>
    </div>
</template>
