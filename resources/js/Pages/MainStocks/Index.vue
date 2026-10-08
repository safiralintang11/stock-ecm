<script setup>
import { ref, watch } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import TextInput from '@/Components/TextInput.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import StockFormModal from '@/Components/StockFormModal.vue'

const props = defineProps({
    products: Array,
    histories: Object,
    filters: Object,
})

const search = ref(props.filters.search ?? '')

watch(search, (s) => {
    router.get(route('main-stocks.index'), { search: s }, { preserveState: true, replace: true, debounce: 300 })
})

const modalOpen = ref(false)

const balances = Object.fromEntries(props.products.map((p) => [p.id, p.main_qty]))

const typeBadge = (type) => (type === 'INITIAL_STOCK' ? 'INITIAL STOCK' : 'STOCK IN')
</script>

<template>
    <Head title="Stock Utama" />

    <AuthenticatedLayout>
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold text-slate-800 sm:text-2xl">Stock Utama</h1>
                <p class="text-sm text-slate-500">Stok yang berada di gudang (satuan PCS)</p>
            </div>
            <PrimaryButton @click="modalOpen = true">+ Input Stock Utama</PrimaryButton>
        </div>

        <div class="grid gap-5 xl:grid-cols-2">
            <!-- Current balances -->
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 p-4">
                    <h2 class="font-bold text-slate-800">Saldo Stock Utama</h2>
                    <TextInput
                        v-model="search"
                        type="text"
                        placeholder="Cari produk atau SKU..."
                        class="mt-3 w-full"
                    />
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[480px] text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                                <th class="px-4 py-3">Produk</th>
                                <th class="px-4 py-3">SKU</th>
                                <th class="px-4 py-3 text-right">Stock Utama</th>
                                <th class="px-4 py-3 text-right">Stock Display</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="products.length === 0">
                                <td colspan="4" class="px-4 py-10 text-center text-slate-400">Tidak ada produk</td>
                            </tr>
                            <tr v-for="p in products" :key="p.id" class="hover:bg-slate-50">
                                <td class="px-4 py-3 font-semibold text-slate-800">{{ p.name }}</td>
                                <td class="px-4 py-3">
                                    <span class="rounded bg-slate-100 px-2 py-0.5 font-mono text-xs text-slate-600">{{ p.sku }}</span>
                                </td>
                                <td class="px-4 py-3 text-right font-bold tabular-nums text-brand-700">{{ p.main_qty }} {{ p.unit }}</td>
                                <td class="px-4 py-3 text-right tabular-nums text-slate-500">{{ p.display_qty }} {{ p.unit }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Stock-in history -->
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 p-4">
                    <h2 class="font-bold text-slate-800">Histori Input Stock Utama</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[560px] text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                                <th class="px-4 py-3">Tanggal</th>
                                <th class="px-4 py-3">Produk</th>
                                <th class="px-4 py-3 text-center">Jenis</th>
                                <th class="px-4 py-3 text-right">Qty</th>
                                <th class="px-4 py-3 text-center">User</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="histories.data.length === 0">
                                <td colspan="5" class="px-4 py-10 text-center text-slate-400">Belum ada input stok</td>
                            </tr>
                            <tr v-for="h in histories.data" :key="h.id" class="hover:bg-slate-50">
                                <td class="px-4 py-3 whitespace-nowrap">{{ h.trx_date }}</td>
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-slate-800">{{ h.product.name }}</div>
                                    <div class="font-mono text-[11px] text-slate-400">{{ h.product.sku }}</div>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span
                                        class="rounded-full px-2 py-0.5 text-[11px] font-semibold"
                                        :class="h.type === 'INITIAL_STOCK' ? 'bg-brown-100 text-brown-700' : 'bg-brand-100 text-brand-700'"
                                    >
                                        {{ typeBadge(h.type) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right font-semibold tabular-nums">+{{ h.qty }} {{ h.product.unit }}</td>
                                <td class="px-4 py-3 text-center text-slate-500">{{ h.user?.name }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="histories.links.length > 3" class="border-t border-slate-100 p-4" v-html="histories.links" />
            </div>
        </div>

        <StockFormModal
            :show="modalOpen"
            title="Input Stock Utama"
            :action="route('main-stocks.store')"
            :products="products"
            :balances="balances"
            balance-label="Stock Utama saat ini"
            submit-label="Tambah Stock"
            @close="modalOpen = false"
        />
    </AuthenticatedLayout>
</template>
