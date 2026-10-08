<script setup>
import { ref, watch } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import TextInput from '@/Components/TextInput.vue'
import StockBadge from '@/Components/StockBadge.vue'

const props = defineProps({
    date: String,
    search: String,
    rows: Array,
})

const date = ref(props.date)
const search = ref(props.search ?? '')

watch([date, search], ([d, s]) => {
    router.get(route('reports.daily-stock'), { date: d, search: s }, { preserveState: true, replace: true, debounce: 300 })
})

const fmt = (n) => Number(n).toLocaleString('id-ID')
</script>

<template>
    <Head title="Daily Stock" />

    <AuthenticatedLayout>
        <div class="mb-5">
            <h1 class="text-xl font-bold text-slate-800 sm:text-2xl">Daily Stock</h1>
            <p class="text-sm text-slate-500">Saldo display harian: sisa hari sebelumnya menjadi stock display awal</p>
        </div>

        <div class="mb-4 flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row">
            <TextInput v-model="date" type="date" class="sm:max-w-[180px]" />
            <TextInput v-model="search" type="text" placeholder="Cari produk atau SKU..." class="w-full sm:max-w-xs" />
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[820px] text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                            <th class="px-4 py-3">Produk</th>
                            <th class="px-4 py-3 text-right">Display Awal</th>
                            <th class="px-4 py-3 text-right">Masuk (Transfer)</th>
                            <th class="px-4 py-3 text-right">OUT</th>
                            <th class="px-4 py-3 text-right">Adjust</th>
                            <th class="px-4 py-3 text-right">Sisa Display</th>
                            <th class="px-4 py-3 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-if="rows.length === 0">
                            <td colspan="7" class="px-4 py-10 text-center text-slate-400">Tidak ada produk</td>
                        </tr>
                        <tr v-for="r in rows" :key="r.id" class="hover:bg-slate-50">
                            <td class="px-4 py-3">
                                <div class="font-semibold text-slate-800">{{ r.name }}</div>
                                <div class="font-mono text-[11px] text-slate-400">{{ r.sku }}</div>
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ fmt(r.opening_qty) }}</td>
                            <td class="px-4 py-3 text-right tabular-nums text-brand-600">+{{ fmt(r.in_qty) }}</td>
                            <td class="px-4 py-3 text-right tabular-nums text-red-600">-{{ fmt(r.out_qty) }}</td>
                            <td class="px-4 py-3 text-right tabular-nums" :class="r.adjust_qty === 0 ? 'text-slate-300' : (r.adjust_qty > 0 ? 'text-brand-600' : 'text-red-600')">
                                {{ r.adjust_qty > 0 ? '+' : '' }}{{ fmt(r.adjust_qty) }}
                            </td>
                            <td class="px-4 py-3 text-right font-bold tabular-nums">{{ fmt(r.closing_qty) }} {{ r.unit }}</td>
                            <td class="px-4 py-3 text-center">
                                <StockBadge :status="r.closing_qty <= 0 ? 'OUT_OF_STOCK' : r.closing_qty <= (r.min_stock ?? 0) ? 'LOW_STOCK' : 'OK'" />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
