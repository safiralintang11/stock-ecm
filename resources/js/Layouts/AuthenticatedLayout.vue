<script setup>
import { computed, ref } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import ApplicationLogo from '@/Components/ApplicationLogo.vue'
import Dropdown from '@/Components/Dropdown.vue'
import DropdownLink from '@/Components/DropdownLink.vue'
import Toast from '@/Components/Toast.vue'

const page = usePage()
const showingNavigationDropdown = ref(false)
const sidebarOpen = ref(false)

const user = computed(() => page.props.auth.user)
const isAdmin = computed(() => user.value?.is_admin)

const nav = computed(() => [
    { label: 'Dashboard', href: route('dashboard'), icon: 'grid', active: route().current('dashboard') },
    {
        label: 'Master Data',
        icon: 'box',
        items: [
            { label: 'Master Produk', href: route('products.index'), active: route().current('products.index') },
        ],
    },
    {
        label: 'Stock',
        icon: 'layers',
        items: [
            { label: 'Stock Utama', href: route('main-stocks.index'), active: route().current('main-stocks.index') },
            { label: 'Stock Display', href: route('display-stocks.index'), active: route().current('display-stocks.index') },
            { label: 'Stock Transfer', href: route('stock-transfers.index'), active: route().current('stock-transfers.index') },
            { label: 'Stock OUT', href: route('stock-outs.index'), active: route().current('stock-outs.index') },
            { label: 'Stock Adjustment', href: route('stock-adjustments.index'), active: route().current('stock-adjustments.index') },
        ],
    },
    {
        label: 'Reports',
        icon: 'chart',
        items: [
            { label: 'Stock History', href: route('reports.stock-history'), active: route().current('reports.stock-history') },
            { label: 'Daily Stock', href: route('reports.daily-stock'), active: route().current('reports.daily-stock') },
            { label: 'Stock Movement', href: route('reports.stock-movement'), active: route().current('reports.stock-movement') },
        ],
    },
    ...(isAdmin.value ? [{
        label: 'Administration',
        icon: 'shield',
        items: [
            { label: 'Users', href: route('users.index'), active: route().current('users.index') },
            { label: 'Audit Log', href: route('audit-logs.index'), active: route().current('audit-logs.index') },
        ],
    }] : []),
])

const icons = {
    grid: 'M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h6v6h-6z',
    box: 'M21 8l-9-5-9 5v8l9 5 9-5V8zM3.3 8.3L12 13l8.7-4.7M12 13v9',
    layers: 'M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5',
    chart: 'M3 3v18h18M7 15v3M12 9v9M17 5v13',
    shield: 'M12 2l8 4v6c0 5-3.5 8.5-8 10-4.5-1.5-8-5-8-10V6l8-4z',
}

const roleBadge = computed(() => (isAdmin.value ? 'Admin' : 'Gudang'))
</script>

<template>
    <div>
        <Head title="StockECM" />

        <div class="min-h-screen bg-cream">
            <!-- Mobile overlay -->
            <div
                v-if="sidebarOpen"
                class="fixed inset-0 z-30 bg-slate-900/50 lg:hidden"
                @click="sidebarOpen = false"
            />

            <!-- Sidebar -->
            <aside
                :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
                class="fixed inset-y-0 left-0 z-40 w-64 transform bg-brown-500 text-cream transition-transform duration-200 lg:translate-x-0"
            >
                <div class="flex h-16 items-center gap-2 border-b border-brown-600 px-5">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-cream font-bold text-brown-500">S</span>
                    <div>
                        <div class="text-sm font-bold text-white">StockECM</div>
                        <div class="text-[10px] uppercase tracking-wider text-cream/70">Warehouse Management</div>
                    </div>
                </div>

                <nav class="space-y-1 overflow-y-auto px-3 py-4 text-sm" style="height: calc(100% - 4rem)">
                    <template v-for="group in nav" :key="group.label">
                        <Link
                            v-if="!group.items"
                            :href="group.href"
                            class="flex items-center gap-3 rounded-lg px-3 py-2.5 font-medium transition"
                            :class="group.active ? 'bg-cream/15 text-brand-300' : 'hover:bg-brown-600 hover:text-white'"
                        >
                            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" :d="icons[group.icon]" />
                            </svg>
                            {{ group.label }}
                        </Link>

                        <div v-else>
                            <div class="px-3 pb-1 pt-4 text-[10px] font-semibold uppercase tracking-wider text-cream/50">
                                {{ group.label }}
                            </div>
                            <Link
                                v-for="item in group.items"
                                :key="item.href"
                                :href="item.href"
                                class="flex items-center gap-3 rounded-lg px-3 py-2 transition"
                                :class="item.active ? 'bg-cream/15 font-semibold text-brand-300' : 'hover:bg-brown-600 hover:text-white'"
                            >
                                <span
                                    class="h-1.5 w-1.5 shrink-0 rounded-full"
                                    :class="item.active ? 'bg-brand-300' : 'bg-cream/40'"
                                />
                                {{ item.label }}
                            </Link>
                        </div>
                    </template>
                </nav>
            </aside>

            <!-- Main -->
            <div class="lg:pl-64">
                <!-- Topbar -->
                <header class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-slate-200 bg-white px-4 sm:px-6">
                    <button
                        class="rounded-lg p-2 text-slate-600 hover:bg-slate-100 lg:hidden"
                        @click="sidebarOpen = !sidebarOpen"
                    >
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    <div class="hidden text-sm font-medium text-slate-500 sm:block">
                        {{ new Date().toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) }}
                    </div>

                    <Dropdown align="right" width="48">
                        <template #trigger>
                            <button class="flex items-center gap-2 rounded-lg px-2 py-1.5 hover:bg-slate-100">
                                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-100 text-sm font-bold text-brand-700">
                                    {{ user?.name?.charAt(0) }}
                                </span>
                                <span class="hidden text-left sm:block">
                                    <span class="block text-sm font-semibold text-slate-800">{{ user?.name }}</span>
                                    <span class="block text-[11px] font-medium text-brand-600">{{ roleBadge }}</span>
                                </span>
                            </button>
                        </template>

                        <template #content>
                            <DropdownLink :href="route('profile.edit')">Profil</DropdownLink>
                            <DropdownLink :href="route('logout')" method="post" as="button">Keluar</DropdownLink>
                        </template>
                    </Dropdown>
                </header>

                <!-- Content -->
                <main class="p-4 sm:p-6">
                    <slot />
                </main>
            </div>

            <Toast />
        </div>
    </div>
</template>
