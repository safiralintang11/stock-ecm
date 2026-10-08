<script setup>
import { computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import Modal from '@/Components/Modal.vue'
import InputError from '@/Components/InputError.vue'
import InputLabel from '@/Components/InputLabel.vue'
import TextInput from '@/Components/TextInput.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'

const props = defineProps({
    show: { type: Boolean, default: false },
    title: { type: String, default: '' },
    maxWidth: { type: String, default: '2xl' },
    product: { type: Object, default: null },
    action: { type: String, required: true }, // inertia route action
    submitLabel: { type: String, default: 'Simpan' },
    fields: { type: Object, required: true }, // { key: { label, type, required, placeholder, min, step } }
    initial: { type: Object, default: () => ({}) },
})

const emit = defineEmits(['close'])

const form = useForm({ ...Object.keys(props.fields).reduce((acc, k) => ({ ...acc, [k]: props.initial[k] ?? '' }), {}) })

// Reset form when modal opens
const key = computed(() => (props.show ? 'open' : 'closed'))
const resetForm = () => {
    Object.keys(props.fields).forEach((k) => {
        form[k] = props.initial[k] ?? ''
    })
    form.clearErrors()
}
</script>

<template>
    <Modal :show="show" :max-width="maxWidth" @close="emit('close')">
        <div class="p-6">
            <h2 class="text-lg font-bold text-slate-800">{{ title }}</h2>

            <form class="mt-4 space-y-4" @submit.prevent="form.post(action, { onSuccess: () => emit('close'), preserveScroll: true })">
                <div v-for="(field, key) in fields" :key="key + key2" class="hidden" />
                <slot />
            </form>
        </div>
    </Modal>
</template>
