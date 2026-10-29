<script setup>
import { ref, watch } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import TextInput from '@/Components/TextInput.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import StockBadge from '@/Components/StockBadge.vue'
import StockFormModal from '@/Components/StockFormModal.vue'

const props = defineProps({
    rows: Array,
    search: String,
})

const search = ref(props.search ?? '')

watch(search, (s) => {
    router.get(route('sisa-display.index'), { search: s }, { preserveState: true, replace: true, debounce: 300 })
})

const modalOpen = ref(false)
const selectedProduct = ref(null)

const openCreate = () => {
    selectedProduct.value = null
    modalOpen.value = true
}

const openInput = (row) => {
    selectedProduct.value = row
    modalOpen.value = true
}

const balances = Object.fromEntries(props.rows.map((r) => [r.id, r.display_qty]))

const fmt = (n) => Number(n).toLocaleString('id-ID')
</script>

<template>
    <Head title="Sisa Display" />

    <AuthenticatedLayout>
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold text-slate-800 sm:text-2xl">Sisa Stock Display</h1>
                <p class="text-sm text-slate-500">Input hasil hitung fisik — selisih otomatis dicatat sebagai Stock OUT</p>
            </div>
            <PrimaryButton @click="openCreate">+ Input Sisa Display</PrimaryButton>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 p-4">
                <TextInput v-model="search" type="text" placeholder="Cari nama produk atau SKU..." class="w-full sm:max-w-xs" />
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[640px] text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                            <th class="px-4 py-3">Produk</th>
                            <th class="px-4 py-3 text-right">Stock Display</th>
                            <th class="px-4 py-3 text-center">Status</th>
                            <th class="px-4 py-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-if="rows.length === 0">
                            <td colspan="4" class="px-4 py-10 text-center text-slate-400">Tidak ada produk</td>
                        </tr>
                        <tr v-for="row in rows" :key="row.id" class="hover:bg-slate-50">
                            <td class="px-4 py-3">
                                <div class="font-semibold text-slate-800">{{ row.name }}</div>
                                <div class="mt-0.5 flex items-center gap-2">
                                    <span class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-[11px] font-semibold text-slate-600">{{ row.sku }}</span>
                                    <span class="text-[11px] text-slate-400">{{ row.unit }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right font-bold tabular-nums text-brand-700">{{ fmt(row.display_qty) }} {{ row.unit }}</td>
                            <td class="px-4 py-3 text-center"><StockBadge :status="row.stock_status" /></td>
                            <td class="px-4 py-3 text-center">
                                <PrimaryButton class="!px-3 !py-1.5" @click="openInput(row)">Isi Sisa</PrimaryButton>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <StockFormModal
            :show="modalOpen"
            title="Input Sisa Stock Display"
            :action="route('sisa-display.store')"
            :products="rows.map((r) => ({ id: r.id, sku: r.sku, name: r.name, unit: r.unit, carton_qty: r.carton_qty }))"
            :initial-product-id="selectedProduct?.id"
            :balances="balances"
            balance-label="Stock Display saat ini"
            :min-qty="0"
            hint="Selisih Stock Display − Sisa Display akan otomatis dicatat sebagai Stock OUT."
            submit-label="Simpan Sisa Display"
            @close="modalOpen = false"
        />
    </AuthenticatedLayout>
</template>
