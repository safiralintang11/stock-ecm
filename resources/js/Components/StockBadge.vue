<script setup>
import { computed } from 'vue'

const props = defineProps({
    status: { type: String, default: 'OK' },
})

const config = computed(() => {
    switch (props.status) {
        case 'LOW_STOCK':
            return { label: 'LOW STOCK', cls: 'bg-amber-100 text-amber-800 ring-amber-200' }
        case 'OUT_OF_STOCK':
            return { label: 'OUT OF STOCK', cls: 'bg-red-100 text-red-700 ring-red-200' }
        case 'inactive':
            return { label: 'INACTIVE', cls: 'bg-slate-200 text-slate-600 ring-slate-300' }
        case 'active':
            return { label: 'ACTIVE', cls: 'bg-brand-100 text-brand-700 ring-brand-300' }
        default:
            return { label: 'OK', cls: 'bg-brand-100 text-brand-700 ring-brand-300' }
    }
})
</script>

<template>
    <span
        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-semibold ring-1"
        :class="config.cls"
    >
        {{ config.label }}
    </span>
</template>
