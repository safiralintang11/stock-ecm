<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center bg-cream pt-6 sm:justify-center sm:pt-0"
    >
        <Link href="/">
            <ApplicationLogo class="h-24 w-auto sm:h-28" />
        </Link>

        <div
            class="mt-6 w-full overflow-hidden bg-white px-6 py-4 shadow-md sm:max-w-md sm:rounded-lg"
        >
            <slot></slot>
        </div>
    </div>
</template>
