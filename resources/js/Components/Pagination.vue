<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
    links: { type: Array, default: () => [] },
})
</script>

<template>
    <nav v-if="links.length > 3" class="flex flex-wrap items-center gap-1" aria-label="Pagination">
        <template v-for="(link, index) in links" :key="`${index}-${link.label}`">
            <Link
                v-if="link.url"
                :href="link.url"
                preserve-scroll
                class="min-w-9 rounded-lg px-3 py-2 text-center text-sm transition"
                :class="link.active
                    ? 'bg-brand-600 font-semibold text-white'
                    : 'text-slate-600 hover:bg-slate-100'"
                v-html="link.label"
            />
            <span
                v-else
                class="min-w-9 rounded-lg px-3 py-2 text-center text-sm text-slate-400"
                v-html="link.label"
            />
        </template>
    </nav>
</template>
