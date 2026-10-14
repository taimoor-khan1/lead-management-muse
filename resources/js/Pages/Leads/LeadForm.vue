<script setup>
defineProps({
    form: Object,
    users: { type: Array, default: () => [] },
});

const stages = ['new', 'contacted', 'replied', 'proposal', 'won', 'lost'];
const websiteStatuses = ['none', 'weak', 'ok'];

const labelCls = 'block text-xs font-semibold mb-1';
const labelStyle = 'color:#72777d;';
const inputCls = 'rounded-lg border text-sm px-3 py-2 w-full focus:outline-none focus:ring-2 focus:ring-orange-200';
const inputStyle = 'border-color:#e5e7e9;color:#181a1c;';
const errCls = 'text-xs mt-1';
const errStyle = 'color:#c64f4f;';
</script>

<template>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="md:col-span-2">
            <label :class="labelCls" :style="labelStyle">Business Name *</label>
            <input v-model="form.business_name" type="text" :class="inputCls" :style="inputStyle" placeholder="e.g. Al-Noor Bakery" />
            <div v-if="form.errors.business_name" :class="errCls" :style="errStyle">{{ form.errors.business_name }}</div>
        </div>

        <div>
            <label :class="labelCls" :style="labelStyle">Contact Person</label>
            <input v-model="form.contact_person" type="text" :class="inputCls" :style="inputStyle" />
            <div v-if="form.errors.contact_person" :class="errCls" :style="errStyle">{{ form.errors.contact_person }}</div>
        </div>
        <div>
            <label :class="labelCls" :style="labelStyle">Phone</label>
            <input v-model="form.phone" type="text" :class="inputCls" :style="inputStyle" placeholder="03xx-xxxxxxx" />
            <div v-if="form.errors.phone" :class="errCls" :style="errStyle">{{ form.errors.phone }}</div>
        </div>

        <div>
            <label :class="labelCls" :style="labelStyle">Email</label>
            <input v-model="form.email" type="email" :class="inputCls" :style="inputStyle" />
            <div v-if="form.errors.email" :class="errCls" :style="errStyle">{{ form.errors.email }}</div>
        </div>
        <div>
            <label :class="labelCls" :style="labelStyle">Website</label>
            <input v-model="form.website" type="text" :class="inputCls" :style="inputStyle" placeholder="https://…" />
            <div v-if="form.errors.website" :class="errCls" :style="errStyle">{{ form.errors.website }}</div>
        </div>

        <div class="md:col-span-2">
            <label :class="labelCls" :style="labelStyle">Address</label>
            <textarea v-model="form.address" rows="2" :class="inputCls" :style="inputStyle"></textarea>
            <div v-if="form.errors.address" :class="errCls" :style="errStyle">{{ form.errors.address }}</div>
        </div>

        <div>
            <label :class="labelCls" :style="labelStyle">Latitude</label>
            <input v-model="form.latitude" type="number" step="any" :class="inputCls" :style="inputStyle" />
            <div v-if="form.errors.latitude" :class="errCls" :style="errStyle">{{ form.errors.latitude }}</div>
        </div>
        <div>
            <label :class="labelCls" :style="labelStyle">Longitude</label>
            <input v-model="form.longitude" type="number" step="any" :class="inputCls" :style="inputStyle" />
            <div v-if="form.errors.longitude" :class="errCls" :style="errStyle">{{ form.errors.longitude }}</div>
        </div>

        <div>
            <label :class="labelCls" :style="labelStyle">City</label>
            <input v-model="form.city" type="text" :class="inputCls" :style="inputStyle" placeholder="Lahore" />
            <div v-if="form.errors.city" :class="errCls" :style="errStyle">{{ form.errors.city }}</div>
        </div>
        <div>
            <label :class="labelCls" :style="labelStyle">Industry</label>
            <input v-model="form.industry" type="text" :class="inputCls" :style="inputStyle" placeholder="Restaurant" />
            <div v-if="form.errors.industry" :class="errCls" :style="errStyle">{{ form.errors.industry }}</div>
        </div>

        <div>
            <label :class="labelCls" :style="labelStyle">Website Status</label>
            <select v-model="form.website_status" :class="inputCls" :style="inputStyle">
                <option v-for="w in websiteStatuses" :key="w" :value="w" class="capitalize">{{ w }}</option>
            </select>
            <div v-if="form.errors.website_status" :class="errCls" :style="errStyle">{{ form.errors.website_status }}</div>
        </div>
        <div>
            <label :class="labelCls" :style="labelStyle">Stage</label>
            <select v-model="form.stage" :class="inputCls" :style="inputStyle">
                <option v-for="s in stages" :key="s" :value="s" class="capitalize">{{ s }}</option>
            </select>
            <div v-if="form.errors.stage" :class="errCls" :style="errStyle">{{ form.errors.stage }}</div>
        </div>

        <div>
            <label :class="labelCls" :style="labelStyle">Suggested Service</label>
            <input v-model="form.suggested_service" type="text" :class="inputCls" :style="inputStyle" placeholder="Website" />
            <div v-if="form.errors.suggested_service" :class="errCls" :style="errStyle">{{ form.errors.suggested_service }}</div>
        </div>
        <div>
            <label :class="labelCls" :style="labelStyle">Score (0–100)</label>
            <input v-model="form.score" type="number" min="0" max="100" :class="inputCls" :style="inputStyle" />
            <div v-if="form.errors.score" :class="errCls" :style="errStyle">{{ form.errors.score }}</div>
        </div>

        <div>
            <label :class="labelCls" :style="labelStyle">Source</label>
            <input v-model="form.source" type="text" :class="inputCls" :style="inputStyle" placeholder="manual" />
            <div v-if="form.errors.source" :class="errCls" :style="errStyle">{{ form.errors.source }}</div>
        </div>
        <div>
            <label :class="labelCls" :style="labelStyle">Owner</label>
            <select v-model="form.owner_id" :class="inputCls" :style="inputStyle">
                <option :value="null">— Unassigned —</option>
                <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
            </select>
            <div v-if="form.errors.owner_id" :class="errCls" :style="errStyle">{{ form.errors.owner_id }}</div>
        </div>

        <div class="md:col-span-2">
            <label :class="labelCls" :style="labelStyle">Notes</label>
            <textarea v-model="form.notes" rows="3" :class="inputCls" :style="inputStyle"></textarea>
            <div v-if="form.errors.notes" :class="errCls" :style="errStyle">{{ form.errors.notes }}</div>
        </div>
    </div>
</template>
