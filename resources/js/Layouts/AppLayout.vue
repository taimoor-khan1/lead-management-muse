<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth.user);

const nav = [
    { name: 'Dashboard', route: 'dashboard', match: ['dashboard'], icon: 'M3 12l9-9 9 9M5 10v10h5v-6h4v6h5V10' },
    { name: 'Leads', route: 'leads.index', match: ['leads.*'], icon: 'M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75' },
    { name: 'Pipeline', route: 'pipeline.index', match: ['pipeline.*'], icon: 'M22 12h-4l-3 9L9 3l-3 9H2' },
    { name: 'Inbox', route: 'inbox.index', match: ['inbox.*'], icon: 'M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2zm18 4l-8 5-8-5' },
    { name: 'Campaigns', route: 'campaigns.index', match: ['campaigns.*'], icon: 'M3 11l18-8-8 18-2.5-7.5L3 11z' },
    { name: 'Proposals', route: 'proposals.index', match: ['proposals.*'], icon: 'M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8l-6-6zm-2 16H8v-2h4v2zm0-4H8v-2h4v2zm3-4H8V7h7v3z' },
    { name: 'Tasks', route: 'tasks.index', match: ['tasks.*'], icon: 'M9 11l3 3L22 4M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11' },
    { name: 'Automation', route: 'automations.index', match: ['automations.*'], icon: 'M13 2L3 14h9l-1 8 10-12h-9l1-8z' },
    { name: 'Reports', route: 'reports.index', match: ['reports.*'], icon: 'M18 20V10M12 20V4M6 20v-6' },
    { name: 'Settings', route: 'settings.index', match: ['settings.*', 'services.*'], icon: 'M12 15a3 3 0 100-6 3 3 0 000 6zm9.4-3a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 11-2.83 2.83l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 11-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 11-2.83-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 110-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 112.83-2.83l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 114 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 112.83 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 110 4h-.09a1.65 1.65 0 00-1.51 1z' },
];

const isActive = (patterns) => patterns.some((p) => route().current(p));

<template>
    <div class="min-h-screen" style="background:#eef0f2; font-family:'DM Sans',sans-serif;">
        <div class="flex">
            <!-- Sidebar -->
            <aside class="hidden md:flex flex-col w-[230px] shrink-0 min-h-screen bg-white border-r sticky top-0 h-screen" style="border-color:#e5e7e9;">
                <div class="px-5 pt-6 pb-4 flex items-center gap-2">
                    <span class="w-8 h-8 rounded-lg flex items-center justify-center text-white font-bold text-lg" style="background:#f26a2e;">L</span>
                    <span class="font-bold text-lg" style="color:#181a1c;">LeadFlow</span>
                </div>
                <nav class="flex-1 px-3 py-2 space-y-1 overflow-y-auto">
                    <Link v-for="item in nav" :key="item.name" :href="route(item.route)"
                        class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition"
                        :style="isActive(item.match)
                            ? 'background:#fff0e8; color:#a73c10;'
                            : 'color:#72777d;'">
                        <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path :d="item.icon"/></svg>
                        {{ item.name }}
                    </Link>
                </nav>
                <div class="p-4 border-t" style="border-color:#e5e7e9;">
                    <div class="flex items-center gap-3">
                        <span class="w-9 h-9 rounded-full flex items-center justify-center text-white text-sm font-semibold" style="background:#f26a2e;">
                            {{ (user?.name || 'U')[0].toUpperCase() }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="text-sm font-semibold truncate" style="color:#181a1c;">{{ user?.name }}</div>
                            <Link :href="route('logout')" method="post" as="button" class="text-xs" style="color:#72777d;">Log out</Link>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- Main -->
            <div class="flex-1 min-w-0">
                <!-- Mobile top bar -->
                <div class="md:hidden bg-white border-b px-4 py-3 flex items-center justify-between sticky top-0 z-10" style="border-color:#e5e7e9;">
                    <span class="font-bold" style="color:#181a1c;">LeadFlow</span>
                    <Link :href="route('logout')" method="post" as="button" class="text-sm" style="color:#72777d;">Log out</Link>
                </div>
                <main class="p-4 md:p-8 max-w-[1400px] mx-auto">
                    <slot />
                </main>
            </div>
        </div>
    </div>
</template>
