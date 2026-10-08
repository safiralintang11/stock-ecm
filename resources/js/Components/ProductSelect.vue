<script setup>
import { computed, ref, watch } from 'vue'

const props = defineProps({
    products: { type: Array, required: true }, // [{id, sku, name, unit, ...}]
    modelValue: { type: [String, Number], default: '' },
    placeholder: { type: String, default: 'Cari nama produk atau SKU...' },
})

const emit = defineEmits(['update:modelValue', 'select'])

const open = ref(false)
const query = ref('')

const filtered = computed(() => {
    const q = query.value.toLowerCase()
    if (!q) return props.products
    return props.products.filter(
        (p) => p.name.toLowerCase().includes(q) || p.sku.toLowerCase().includes(q)
    )
})

const selected = computed(() => props.products.find((p) => String(p.id) === String(props.modelValue)))

watch(
    () => props.modelValue,
    () => {
        query.value = ''
    }
)

const choose = (p) => {
    emit('update:modelValue', p.id)
    emit('select', p)
    open.value = false
    query.value = ''
}

const clear = () => {
    emit('update:modelValue', '')
    emit('select', null)
}
</script>

<template>
    <div class="relative">
        <div
            v-if="selected"
            class="flex items-center justify-between rounded-lg border border-brand-300 bg-brand-50 px-3 py-2.5"
        >
            <div class="text-sm">
                <span class="font-semibold text-slate-800">{{ selected.name }}</span>
                <span class="ml-2 rounded bg-slate-200 px-1.5 py-0.5 text-[11px] font-semibold text-slate-600">{{ selected.sku }}</span>
                <span class="ml-1 text-xs text-slate-500">{{ selected.unit }}</span>
            </div>
            <button type="button" class="text-slate-400 hover:text-red-500" @click="clear">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div v-else>
            <input
                v-model="query"
                type="text"
                class="w-full rounded-lg border-slate-300 focus:border-brand-500 focus:ring-brand-500"
                :placeholder="placeholder"
                @focus="open = true"
                @blur="open = false"
            >
            <ul
                v-if="open"
                class="absolute z-20 mt-1 max-h-56 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white py-1 shadow-lg"
            >
                <li v-if="filtered.length === 0" class="px-3 py-2 text-sm text-slate-400">Produk tidak ditemukan</li>
                <li
                    v-for="p in filtered"
                    :key="p.id"
                    class="cursor-pointer px-3 py-2 text-sm hover:bg-brand-50"
                    @mousedown.prevent="choose(p)"
                >
                    <span class="font-medium text-slate-800">{{ p.name }}</span>
                    <span class="ml-2 rounded bg-slate-100 px-1.5 py-0.5 text-[11px] font-semibold text-slate-500">{{ p.sku }}</span>
                </li>
            </ul>
        </div>
    </div>
</template>
