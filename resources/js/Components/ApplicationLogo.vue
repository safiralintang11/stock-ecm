<script setup>
defineProps({
    alt: { type: String, default: 'StockECM' },
})
</script>

<template>
    <img
        src="/logo.png"
        :alt="alt"
        class="block object-contain"
        draggable="false"
    />
</template>
