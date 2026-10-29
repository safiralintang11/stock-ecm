<script setup>
import { ref, watch, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import TextInput from '@/Components/TextInput.vue'
import StatCard from '@/Components/StatCard.vue'
import StockBadge from '@/Components/StockBadge.vue'
import MovementChart from '@/Components/MovementChart.vue'

const props = defineProps({
    date: String,
    search: String,
    cards: Object,
    rows: Array,
    movement: Object,
})

const date = ref(props.date)
const search = ref(props.search ?? '')

watch([date, search], ([d, s]) => {
    router.get(route('dashboard'), { date: d, search: s }, { preserveState: true, replace: true, debounce: 300 })
})

const fmt = (n) => Number(n).toLocaleString('id-ID')

const datasets = computed(() => [
    {
        label: 'Stock Masuk (Utama)',
        data: props.movement.stock_in,
        borderColor: '#2C6334',
        backgroundColor: 'rgba(44,99,52,0.10)',
        fill: true,
        tension: 0.35,
    },
    {
        label: 'Transfer ke Display',
        data: props.movement.transfer,
        borderColor: '#8FBC94',
        backgroundColor: 'rgba(143,188,148,0.14)',
        fill: true,
        tension: 0.35,
    },
    {
        label: 'OUT',
        data: props.movement.out,
        borderColor: '#FF788D',
        backgroundColor: 'rgba(255,120,141,0.10)',
        fill: true,
        tension: 0.35,
    },
])

const shortDate = (d) => new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' })
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <div class="mb-5">
            <h1 class="text-xl font-bold text-slate-800 sm:text-2xl">Dashboard</h1>
            <p class="text-sm text-slate-500">Ringkasan stok gudang & display</p>
        </div>

        <!-- Cards -->
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
            <StatCard label="Total Produk" :value="fmt(cards.total_products)" tone="slate" />
            <StatCard label="Stock Utama" :value="fmt(cards.total_main_stock)" tone="blue" sub="PCS di gudang" />
            <StatCard label="Stock Display" :value="fmt(cards.total_display_stock)" tone="emerald" sub="PCS tersedia" />
            <StatCard label="All Stock" :value="fmt(cards.total_all_stock)" tone="cream" sub="Utama + Display" />
            <StatCard label="OUT Hari Ini" :value="fmt(cards.out_today)" tone="brown" />
            <StatCard label="Low Stock" :value="fmt(cards.low_stock)" tone="amber" :sub="`${cards.out_of_stock} out of stock`" />
        </div>

        <!-- Filters -->
        <div class="mt-5 flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row">
            <TextInput v-model="date" type="date" class="sm:max-w-[180px]" />
            <TextInput v-model="search" type="text" placeholder="Filter SKU / nama produk..." class="w-full sm:max-w-xs" />
        </div>

        <!-- Stock table -->
        <div class="mt-4 rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 p-4">
                <h2 class="font-bold text-slate-800">Stock Hari Ini</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[820px] text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                            <th class="px-4 py-3">SKU</th>
                            <th class="px-4 py-3">Produk</th>
                            <th class="px-4 py-3 text-right">Stock Utama</th>
                            <th class="px-4 py-3 text-right">Display</th>
                            <th class="px-4 py-3 text-right">OUT Hari Ini</th>
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
                                <span class="rounded bg-slate-100 px-2 py-0.5 font-mono text-xs font-semibold text-slate-600">{{ r.sku }}</span>
                            </td>
                            <td class="px-4 py-3 font-semibold text-slate-800">{{ r.name }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ fmt(r.main_qty) }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ fmt(r.display_qty) }}</td>
                            <td class="px-4 py-3 text-right tabular-nums text-red-600">{{ fmt(r.out_today) }}</td>
                            <td class="px-4 py-3 text-right font-bold tabular-nums">{{ fmt(r.sisa_display) }}</td>
                            <td class="px-4 py-3 text-center"><StockBadge :status="r.stock_status" /></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Chart -->
        <div class="mt-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <h2 class="mb-3 font-bold text-slate-800">Grafik Stock Movement (7 hari terakhir)</h2>
            <MovementChart :labels="movement.dates.map(shortDate)" :datasets="datasets" :height="280" />
        </div>
    </AuthenticatedLayout>
</template>
