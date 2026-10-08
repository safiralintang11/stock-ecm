<script setup>
import Modal from '@/Components/Modal.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import DangerButton from '@/Components/DangerButton.vue'

defineProps({
    show: { type: Boolean, default: false },
    title: { type: String, default: 'Konfirmasi' },
    message: { type: String, required: true },
    confirmLabel: { type: String, default: 'Ya, Lanjutkan' },
    busy: { type: Boolean, default: false },
})

const emit = defineEmits(['close', 'confirm'])
</script>

<template>
    <Modal :show="show" max-width="md" @close="emit('close')">
        <div class="p-6">
            <h2 class="text-lg font-bold text-slate-800">{{ title }}</h2>
            <p class="mt-2 text-sm text-slate-600">{{ message }}</p>

            <div class="mt-6 flex justify-end gap-2">
                <SecondaryButton @click="emit('close')">Batal</SecondaryButton>
                <DangerButton :disabled="busy" @click="emit('confirm')">{{ confirmLabel }}</DangerButton>
            </div>
        </div>
    </Modal>
</template>
