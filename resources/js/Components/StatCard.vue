<script setup>
defineProps({
    label: { type: String, required: true },
    value: { type: [String, Number], required: true },
    sub: { type: String, default: '' },
    tone: { type: String, default: 'slate' },
})

const tones = {
    slate: 'bg-white border-slate-200 text-slate-800',
    emerald: 'bg-brand-50 border-brand-200 text-brand-900',
    amber: 'bg-accent-50 border-accent-200 text-accent-900',
    red: 'bg-red-50 border-red-200 text-red-900',
    blue: 'bg-brand-100 border-brand-300 text-brand-800',
    brown: 'bg-brown-50 border-brown-200 text-brown-900',
    cream: 'bg-cream border-cream-dark text-brown-500',
}
</script>

<template>
    <div class="rounded-2xl border p-4 shadow-sm" :class="tones[tone]">
        <div class="text-xs font-semibold uppercase tracking-wide opacity-70">{{ label }}</div>
        <div class="mt-1 text-2xl font-bold tabular-nums sm:text-3xl">{{ value }}</div>
        <div v-if="sub" class="mt-0.5 text-xs opacity-70">{{ sub }}</div>
    </div>
</template>
