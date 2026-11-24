<script setup>
import { computed } from 'vue'

const props = defineProps({
    variant: { type: String, default: 'solid' },
})

const inverse = computed(() => props.variant === 'inverse')

const background = computed(() => (inverse.value ? '#F6F1E7' : '#2C6334'))
const line = computed(() => (inverse.value ? '#2C6334' : '#F6F1E7'))
const accent = computed(() => (inverse.value ? '#5F9A67' : '#8FBC94'))
</script>

<template>
    <svg
        viewBox="0 0 64 64"
        xmlns="http://www.w3.org/2000/svg"
        role="img"
        aria-label="StockECM"
    >
        <rect width="64" height="64" rx="14" :fill="background" />
        <rect
            x="17.75"
            y="19.75"
            width="28.5"
            height="24.5"
            rx="3.25"
            fill="none"
            :stroke="line"
            stroke-width="3.5"
        />
        <path d="M17 27.5h30" :stroke="line" stroke-width="3.5" />
        <path d="M26 36h12" :stroke="accent" stroke-width="3.5" stroke-linecap="round" />
    </svg>
</template>
