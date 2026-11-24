<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center bg-cream pt-6 sm:justify-center sm:pt-0"
    >
        <Link href="/" class="flex flex-col items-center gap-2">
            <ApplicationLogo class="h-16 w-16" />
            <span class="text-center">
                <span class="block text-lg font-bold tracking-tight text-brand-800">StockECM</span>
                <span class="block text-[10px] uppercase tracking-[0.2em] text-slate-500">Warehouse Management</span>
            </span>
        </Link>

        <div
            class="mt-6 w-full overflow-hidden bg-white px-6 py-4 shadow-md sm:max-w-md sm:rounded-lg"
        >
            <slot></slot>
        </div>
    </div>
</template>
