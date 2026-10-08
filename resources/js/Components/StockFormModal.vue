<script setup>
import { computed, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import Modal from '@/Components/Modal.vue'
import InputError from '@/Components/InputError.vue'
import InputLabel from '@/Components/InputLabel.vue'
import TextInput from '@/Components/TextInput.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import ProductSelect from '@/Components/ProductSelect.vue'

const props = defineProps({
    show: { type: Boolean, default: false },
    title: { type: String, required: true },
    action: { type: String, required: true },
    products: { type: Array, default: () => [] },
    mode: { type: String, default: 'qty' }, // qty | adjustment
    initialProductId: { type: [String, Number], default: '' },
    balanceLabel: { type: String, default: '' },
    balance: { type: Number, default: null },
    balances: { type: Object, default: null }, // map product_id -> available balance
    submitLabel: { type: String, default: 'Simpan' },
    maxWidth: { type: String, default: 'lg' },
})

const emit = defineEmits(['close'])

const today = new Date().toISOString().slice(0, 10)

const form = useForm({
    product_id: '',
    qty: '',
    trx_date: today,
    note: '',
    scope: 'display',
    reason: '',
})

watch(
    () => props.show,
    (open) => {
        if (open) {
            form.product_id = props.initialProductId || ''
            form.qty = ''
            form.trx_date = today
            form.note = ''
            form.scope = 'display'
            form.reason = ''
            form.clearErrors()
        }
    }
)

const selectedProduct = computed(() => props.products.find((p) => String(p.id) === String(form.product_id)))

const currentBalance = computed(() => {
    if (props.balances && form.product_id !== '') {
        return props.balances[String(form.product_id)] ?? null
    }
    return props.balance
})

const overBalance = computed(() => {
    if (currentBalance.value === null || form.qty === '' || props.mode === 'adjustment') return false
    return Number(form.qty) > currentBalance.value
})

const submit = () => {
    form.post(props.action, {
        preserveScroll: true,
        onSuccess: () => emit('close'),
    })
}
</script>

<template>
    <Modal :show="show" :max-width="maxWidth" @close="emit('close')">
        <div class="p-6">
            <h2 class="text-lg font-bold text-slate-800">{{ title }}</h2>

            <form class="mt-4 space-y-4" @submit.prevent="submit">
                <div>
                    <InputLabel for="product" value="Produk *" />
                    <div class="mt-1.5">
                        <ProductSelect
                            :products="products"
                            v-model="form.product_id"
                            placeholder="Cari nama produk atau SKU..."
                        />
                    </div>
                    <InputError class="mt-2" :message="form.errors.product_id" />
                </div>

                <div v-if="mode === 'adjustment'" class="grid grid-cols-2 gap-3">
                    <div>
                        <InputLabel for="scope" value="Jenis Stok *" />
                        <select
                            id="scope"
                            v-model="form.scope"
                            class="mt-1.5 w-full rounded-lg border-slate-300 focus:border-brand-500 focus:ring-brand-500"
                        >
                            <option value="display">Stock Display</option>
                            <option value="main">Stock Utama</option>
                        </select>
                        <InputError class="mt-2" :message="form.errors.scope" />
                    </div>
                    <div>
                        <InputLabel for="adj_qty" value="Penyesuaian (+/-) *" />
                        <TextInput
                            id="adj_qty"
                            v-model="form.qty"
                            type="number"
                            class="mt-1.5 block w-full"
                            placeholder="contoh: -5 atau 10"
                            required
                        />
                        <InputError class="mt-2" :message="form.errors.qty" />
                    </div>
                </div>

                <div v-else class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <InputLabel for="qty" value="Qty *" />
                        <TextInput
                            id="qty"
                            v-model="form.qty"
                            type="number"
                            min="1"
                            class="mt-1.5 block w-full"
                            :placeholder="selectedProduct ? `dalam ${selectedProduct.unit}` : 'Qty'"
                            required
                        />
                        <InputError class="mt-2" :message="form.errors.qty" />
                        <p v-if="overBalance" class="mt-1 text-xs font-medium text-red-600">
                            Melebihi {{ balanceLabel }} yang tersedia ({{ currentBalance }}).
                        </p>
                        <p v-else-if="selectedProduct && currentBalance !== null" class="mt-1 text-xs text-slate-500">
                            {{ balanceLabel }}: <span class="font-semibold text-slate-700">{{ currentBalance }} {{ selectedProduct.unit }}</span>
                        </p>
                        <p v-if="selectedProduct" class="mt-1 text-xs text-slate-500">
                            Isi Carton: <span class="font-semibold text-slate-700">{{ selectedProduct.carton_qty }}</span>
                        </p>
                    </div>
                    <div>
                        <InputLabel for="trx_date" value="Tanggal *" />
                        <TextInput
                            id="trx_date"
                            v-model="form.trx_date"
                            type="date"
                            :max="today"
                            class="mt-1.5 block w-full"
                            required
                        />
                        <InputError class="mt-2" :message="form.errors.trx_date" />
                    </div>
                </div>

                <div v-if="mode === 'adjustment'">
                    <InputLabel for="reason" value="Alasan *" />
                    <TextInput
                        id="reason"
                        v-model="form.reason"
                        type="text"
                        class="mt-1.5 block w-full"
                        placeholder="Wajib diisi, contoh: Barang rusak"
                        required
                    />
                    <InputError class="mt-2" :message="form.errors.reason" />
                </div>

                <div>
                    <InputLabel for="note" value="Keterangan" />
                    <TextInput
                        id="note"
                        v-model="form.note"
                        type="text"
                        class="mt-1.5 block w-full"
                        placeholder="Opsional"
                    />
                    <InputError class="mt-2" :message="form.errors.note" />
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <SecondaryButton type="button" @click="emit('close')">Batal</SecondaryButton>
                    <PrimaryButton :disabled="form.processing">{{ submitLabel }}</PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>
</template>
