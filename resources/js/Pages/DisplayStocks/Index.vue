<script setup>
import { ref, watch } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import TextInput from '@/Components/TextInput.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import StockBadge from '@/Components/StockBadge.vue'
import StockFormModal from '@/Components/StockFormModal.vue'

const props = defineProps({
    date: String,
    search: String,
    rows: Array,
})

const query = ref(props.search ?? '')
const date = ref(props.date)

watch([query, date], ([s, d]) => {
    router.get(route('display-stocks.index'), { search: s, date: d }, { preserveState: true, replace: true, debounce: 300 })
})

const modalOpen = ref(false)
const selectedProduct = ref(null)

const openAdd = (row) => {
    selectedProduct.value = row
    modalOpen.value = true
}

const fmt = (n) => Number(n).toLocaleString('id-ID')
</script>

<template>
    <Head title="Stock Display" />

    <AuthenticatedLayout>
        <div class="mb-5">
            <h1 class="text-xl font-bold text-slate-800 sm:text-2xl">Stock Display</h1>
            <p class="text-sm text-slate-500">Stok harian yang digunakan untuk kebutuhan operasional</p>
        </div>

        <div class="mb-4 flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row">
            <TextInput v-model="query" type="text" placeholder="Cari nama produk atau SKU..." class="w-full sm:max-w-xs" />
            <TextInput v-model="date" type="date" class="w-full sm:max-w-[180px]" />
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <div v-if="rows.length === 0" class="col-span-full rounded-2xl border border-slate-200 bg-white p-10 text-center text-slate-400 shadow-sm">
                Produk tidak ditemukan
            </div>

            <div
                v-for="row in rows"
                :key="row.id"
                class="rounded-2xl border bg-white p-4 shadow-sm transition hover:shadow-md"
                :class="row.stock_status === 'OUT_OF_STOCK' ? 'border-red-200' : row.stock_status === 'LOW_STOCK' ? 'border-amber-200' : 'border-slate-200'"
            >
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <div class="font-bold text-slate-800">{{ row.name }}</div>
                        <div class="mt-0.5 flex items-center gap-2">
                            <span class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-[11px] font-semibold text-slate-600">{{ row.sku }}</span>
                            <span class="text-[11px] text-slate-400">{{ row.unit }}</span>
                        </div>
                    </div>
                    <StockBadge :status="row.stock_status" />
                </div>

                <div class="mt-3 grid grid-cols-2 gap-2 text-sm">
                    <div class="rounded-lg bg-slate-50 p-2.5">
                        <div class="text-[11px] font-medium text-slate-500">Stock Utama (Masuk)</div>
                        <div class="font-bold tabular-nums text-slate-800">{{ fmt(row.total_received) }}</div>
                    </div>
                    <div class="rounded-lg bg-brand-50 p-2.5">
                        <div class="text-[11px] font-medium text-brand-600">Stock Display</div>
                        <div class="font-bold tabular-nums text-brand-700">{{ fmt(row.display_qty) }}</div>
                    </div>
                    <div class="rounded-lg bg-brown-50 p-2.5">
                        <div class="text-[11px] font-medium text-brown-500">Sisa Stock Utama</div>
                        <div class="font-bold tabular-nums text-brown-700">{{ fmt(row.main_qty) }}</div>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-2.5">
                        <div class="text-[11px] font-medium text-slate-500">Hari Ini (IN / OUT)</div>
                        <div class="font-bold tabular-nums text-slate-800">{{ fmt(row.in_qty) }} / {{ fmt(row.out_qty) }}</div>
                    </div>
                </div>

                <div class="mt-3 flex gap-2">
                    <PrimaryButton class="flex-1 justify-center" @click="openAdd(row)">+ Tambah Display</PrimaryButton>
                </div>
            </div>
        </div>

        <StockFormModal
            :show="modalOpen"
            title="Tambah Stock Display"
            :action="route('display-stocks.store')"
            :products="rows.map((r) => ({ id: r.id, sku: r.sku, name: r.name, unit: r.unit, carton_qty: r.carton_qty }))"
            :initial-product-id="selectedProduct?.id"
            balance-label="Stock Utama tersedia"
            :balance="selectedProduct?.main_qty ?? null"
            submit-label="Transfer ke Display"
            @close="modalOpen = false"
        />
    </AuthenticatedLayout>
</template>
