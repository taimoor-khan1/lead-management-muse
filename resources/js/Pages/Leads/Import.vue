<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineOptions({ layout: AppLayout });

const flash = computed(() => usePage().props.flash || {});
const fileInput = ref(null);

const form = useForm({ file: null });

function pickFile(e) {
    form.file = e.target.files[0] || null;
}

function submit() {
    form.post(route('leads.import'), { forceFormData: true });
}

const cardStyle = 'background:#fff;border:1px solid #e5e7e9;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,.05);';
</script>

<template>
    <Head title="Import Leads" />

    <div class="p-6 max-w-[720px] mx-auto">
        <div class="flex items-center justify-between mb-5">
            <h1 class="text-xl font-bold" style="color:#181a1c;">Import Leads</h1>
            <Link :href="route('leads.index')" class="text-sm font-semibold" style="color:#72777d;">← Back to leads</Link>
        </div>

        <div v-if="flash.message" class="mb-4 px-4 py-3 rounded-lg text-sm"
            style="background:#eaf7f1;color:#268866;border:1px solid #cdeadb;">{{ flash.message }}</div>
        <div v-if="flash.error" class="mb-4 px-4 py-3 rounded-lg text-sm"
            style="background:#fff0f0;color:#c64f4f;border:1px solid #f3d2d2;">{{ flash.error }}</div>

        <div class="p-6" :style="cardStyle">
            <p class="text-sm mb-2" style="color:#72777d;">
                Upload a CSV file (same columns as a Google Sheet export). Existing business names are skipped automatically.
            </p>
            <p class="text-xs mb-4" style="color:#72777d;">
                Expected headers: Business Name, Contact Person, Phone, Email, Address, City, Industry, Website, Notes, Suggested Service
            </p>

            <form @submit.prevent="submit">
                <input ref="fileInput" type="file" accept=".csv,.txt" @change="pickFile"
                    class="block w-full text-sm rounded-lg border px-3 py-2 mb-2"
                    style="border-color:#e5e7e9;color:#181a1c;" />
                <div v-if="form.errors.file" class="text-xs mb-2" style="color:#c64f4f;">{{ form.errors.file }}</div>

                <div class="flex justify-end gap-2 mt-4">
                    <Link :href="route('leads.index')" class="px-4 py-2 rounded-lg text-sm font-semibold border"
                        style="border-color:#e5e7e9;color:#181a1c;background:#fff;">Cancel</Link>
                    <button type="submit" :disabled="form.processing || !form.file"
                        class="px-5 py-2 rounded-lg text-sm font-semibold text-white disabled:opacity-60"
                        style="background:#f26a2e;">
                        {{ form.processing ? 'Importing…' : 'Import CSV' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
