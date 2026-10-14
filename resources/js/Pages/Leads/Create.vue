<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import LeadForm from './LeadForm.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineOptions({ layout: AppLayout });

defineProps({ users: Array });

const form = useForm({
    business_name: '',
    contact_person: '',
    phone: '',
    email: '',
    address: '',
    latitude: '',
    longitude: '',
    website: '',
    website_status: 'none',
    city: '',
    industry: '',
    notes: '',
    suggested_service: '',
    stage: 'new',
    score: 0,
    source: 'manual',
    owner_id: null,
});

function submit() {
    form.post(route('leads.store'));
}

const cardStyle = 'background:#fff;border:1px solid #e5e7e9;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,.05);';
</script>

<template>
    <Head title="Add Lead" />

    <div class="p-6 max-w-[860px] mx-auto">
        <div class="flex items-center justify-between mb-5">
            <h1 class="text-xl font-bold" style="color:#181a1c;">Add Lead</h1>
            <Link :href="route('leads.index')" class="text-sm font-semibold" style="color:#72777d;">← Back to leads</Link>
        </div>

        <form @submit.prevent="submit" class="p-6" :style="cardStyle">
            <LeadForm :form="form" :users="users" />

            <div class="flex justify-end gap-2 mt-6">
                <Link :href="route('leads.index')" class="px-4 py-2 rounded-lg text-sm font-semibold border"
                    style="border-color:#e5e7e9;color:#181a1c;background:#fff;">Cancel</Link>
                <button type="submit" :disabled="form.processing"
                    class="px-5 py-2 rounded-lg text-sm font-semibold text-white disabled:opacity-60"
                    style="background:#f26a2e;">
                    {{ form.processing ? 'Saving…' : 'Save Lead' }}
                </button>
            </div>
        </form>
    </div>
</template>
