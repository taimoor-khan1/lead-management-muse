<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    proposal: Object,
});

const copied = ref(false);

const pill = (s) => {
    const map = {
        draft: 'background:#eef0f2; color:#72777d;',
        sent: 'background:#edf5ff; color:#3178c6;',
        accepted: 'background:#eaf7f1; color:#268866;',
        declined: 'background:#fff0f0; color:#c64f4f;',
    };
    return map[s] || map.draft;
};

const pkr = (n) => 'PKR ' + Number(n || 0).toLocaleString('en-PK');

function copyLink() {
    const done = () => {
        copied.value = true;
        setTimeout(() => { copied.value = false; }, 1500);
    };
    if (navigator.clipboard?.writeText) {
        navigator.clipboard.writeText(props.proposal.public_url).then(done).catch(done);
    } else {
        done();
    }
}

function sendProposal() {
    if (confirm('Send this proposal?')) {
        router.post(route('proposals.send', props.proposal.id));
    }
}

function destroy() {
    if (confirm('Delete this proposal? This cannot be undone.')) {
        router.delete(route('proposals.destroy', props.proposal.id));
    }
}
</script>

<template>
    <Head :title="proposal.title" />

    <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
        <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold" style="color:#181a1c;">{{ proposal.title }}</h1>
            <span class="px-2.5 py-1 rounded-full text-xs font-semibold capitalize" :style="pill(proposal.status)">{{ proposal.status }}</span>
        </div>
        <div class="flex gap-2">
            <Link :href="route('proposals.edit', proposal.id)" class="px-4 py-2 rounded-lg text-sm font-semibold border bg-white"
                style="border-color:#e5e7e9; color:#72777d;">Edit</Link>
            <button v-if="proposal.status === 'draft'" @click="sendProposal"
                class="px-4 py-2 rounded-lg text-sm font-semibold text-white" style="background:#f26a2e;">Send proposal</button>
            <button @click="destroy" class="px-4 py-2 rounded-lg text-sm font-semibold border bg-white" style="border-color:#e5e7e9; color:#c64f4f;">Delete</button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-xl p-6" style="box-shadow:0 1px 4px rgba(0,0,0,.05);">
                <h2 class="font-semibold mb-4" style="color:#181a1c;">Items</h2>
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left" style="color:#72777d; border-bottom:1px solid #e5e7e9;">
                            <th class="py-2 font-medium">Item</th>
                            <th class="py-2 font-medium text-right">Qty</th>
                            <th class="py-2 font-medium text-right">Price</th>
                            <th class="py-2 font-medium text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in proposal.items" :key="item.id" style="border-bottom:1px solid #eef0f2;">
                            <td class="py-2.5 font-medium" style="color:#181a1c;">{{ item.name }}</td>
                            <td class="py-2.5 text-right" style="color:#72777d;">{{ item.qty }}</td>
                            <td class="py-2.5 text-right" style="color:#72777d;">{{ pkr(item.price) }}</td>
                            <td class="py-2.5 text-right font-semibold">{{ pkr(item.qty * item.price) }}</td>
                        </tr>
                    </tbody>
                </table>
                <div class="max-w-[280px] ml-auto mt-4">
                    <div class="flex justify-between text-sm py-1" style="color:#72777d;"><span>Subtotal</span><span>{{ pkr(proposal.subtotal) }}</span></div>
                    <div class="flex justify-between text-sm py-1" style="color:#72777d;"><span>Discount</span><span>− {{ pkr(proposal.discount) }}</span></div>
                    <div class="flex justify-between text-lg font-bold py-2 mt-1" style="color:#181a1c; border-top:2px solid #f26a2e;"><span>Total</span><span>{{ pkr(proposal.total) }}</span></div>
                </div>
            </div>

            <div v-if="proposal.notes" class="bg-white rounded-xl p-6" style="box-shadow:0 1px 4px rgba(0,0,0,.05);">
                <h2 class="font-semibold mb-2" style="color:#181a1c;">Notes</h2>
                <p class="text-sm whitespace-pre-wrap" style="color:#72777d;">{{ proposal.notes }}</p>
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-xl p-6" style="box-shadow:0 1px 4px rgba(0,0,0,.05);">
                <h2 class="font-semibold mb-3" style="color:#181a1c;">Lead</h2>
                <div class="text-sm font-semibold" style="color:#181a1c;">{{ proposal.lead?.business_name }}</div>
                <div class="text-sm" style="color:#72777d;">{{ proposal.lead?.contact_person }}</div>
                <div class="text-sm" style="color:#72777d;">{{ proposal.lead?.city }}</div>
                <div class="text-sm mt-2" style="color:#72777d;">Valid until: {{ proposal.valid_until || '—' }}</div>
            </div>

            <div class="bg-white rounded-xl p-6" style="box-shadow:0 1px 4px rgba(0,0,0,.05);">
                <h2 class="font-semibold mb-3" style="color:#181a1c;">Public link</h2>
                <p class="text-xs mb-3 break-all" style="color:#72777d;">{{ proposal.public_url }}</p>
                <div class="flex gap-2">
                    <button @click="copyLink" class="flex-1 px-4 py-2 rounded-lg text-sm font-semibold text-white" style="background:#f26a2e;">
                        {{ copied ? 'Copied!' : 'Copy link' }}
                    </button>
                    <a :href="proposal.public_url" target="_blank" rel="noopener"
                        class="px-4 py-2 rounded-lg text-sm font-semibold border bg-white" style="border-color:#e5e7e9; color:#72777d;">Open</a>
                </div>
                <p class="text-xs mt-3" style="color:#72777d;">Share this link with the client. They can accept or decline without logging in.</p>
            </div>
        </div>
    </div>
</template>
