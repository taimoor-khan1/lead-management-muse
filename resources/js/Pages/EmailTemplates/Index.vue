<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineOptions({ layout: AppLayout });

defineProps({ templates: Object });

const destroy = (id) => {
    if (confirm('Delete this template?')) {
        router.delete(route('email-templates.destroy', id));
    }
};
</script>

<template>
    <Head title="Email Templates" />

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold" style="color:#181a1c;">Email Templates</h1>
            <p class="text-sm mt-1" style="color:#72777d;">Reusable outreach templates with personalization variables.</p>
        </div>
        <Link :href="route('email-templates.create')" class="px-4 py-2 rounded-lg text-sm font-semibold text-white" style="background:#f26a2e;">
            + New Template
        </Link>
    </div>

    <div class="bg-white rounded-xl border shadow-sm overflow-hidden" style="border-color:#e5e7e9;">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left border-b" style="border-color:#e5e7e9;">
                    <th class="px-5 py-3 font-semibold" style="color:#72777d;">Name</th>
                    <th class="px-5 py-3 font-semibold" style="color:#72777d;">Subject</th>
                    <th class="px-5 py-3 font-semibold" style="color:#72777d;">Updated</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="t in templates.data" :key="t.id" class="border-b last:border-0" style="border-color:#e5e7e9;">
                    <td class="px-5 py-3 font-semibold" style="color:#181a1c;">{{ t.name }}</td>
                    <td class="px-5 py-3" style="color:#72777d;">{{ t.subject }}</td>
                    <td class="px-5 py-3 whitespace-nowrap" style="color:#72777d;">{{ t.updated_at }}</td>
                    <td class="px-5 py-3 text-right whitespace-nowrap">
                        <Link :href="route('email-templates.edit', t.id)" class="text-sm font-medium mr-4" style="color:#3178c6;">Edit</Link>
                        <button @click="destroy(t.id)" class="text-sm font-medium" style="color:#c64f4f;">Delete</button>
                    </td>
                </tr>
                <tr v-if="!templates.data.length">
                    <td colspan="4" class="px-5 py-10 text-center" style="color:#72777d;">
                        No templates yet. Create your first outreach template.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
