<script setup>
import { ref, watch, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import TextInput from '@/Components/TextInput.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import MovementChart from '@/Components/MovementChart.vue'

const props = defineProps({
    from_date: String,
    to_date: String,
    series: Object,
})

const from = ref(props.from_date)
const to = ref(props.to_date)

watch([from, to], ([f, t]) => {
    router.get(route('reports.stock-movement'), { from_date: f, to_date: t }, { preserveState: true, replace: true })
})

const datasets = computed(() => [
    {
        label: 'Stock Masuk (Utama)',
        data: props.series.stock_in,
        borderColor: '#597928',
        backgroundColor: 'rgba(89,121,40,0.10)',
        fill: true,
        tension: 0.35,
    },
    {
        label: 'Transfer ke Display',
        data: props.series.transfer,
        borderColor: '#91AC67',
        backgroundColor: 'rgba(145,172,103,0.14)',
        fill: true,
        tension: 0.35,
    },
    {
        label: 'OUT',
        data: props.series.out,
        borderColor: '#6E3511',
        backgroundColor: 'rgba(110,53,17,0.08)',
        fill: true,
        tension: 0.35,
    },
])

const shortDate = (d) => new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' })

const totals = computed(() => ({
    stock_in: props.series.stock_in.reduce((a, b) => a + b, 0),
    transfer: props.series.transfer.reduce((a, b) => a + b, 0),
    out: props.series.out.reduce((a, b) => a + b, 0),
}))
</script>

<template>
    <Head title="Stock Movement" />

    <AuthenticatedLayout>
        <div class="mb-5">
            <h1 class="text-xl font-bold text-slate-800 sm:text-2xl">Stock Movement</h1>
            <p class="text-sm text-slate-500">Pergerakan stok berdasarkan tanggal</p>
        </div>

        <div class="mb-4 flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-end">
            <div>
                <label class="text-xs font-semibold text-slate-500">Dari</label>
                <TextInput v-model="from" type="date" class="mt-1 block sm:max-w-[180px]" />
            </div>
            <div>
                <label class="text-xs font-semibold text-slate-500">Sampai</label>
                <TextInput v-model="to" type="date" class="mt-1 block sm:max-w-[180px]" />
            </div>
            <SecondaryButton>Periode aktif</SecondaryButton>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-2xl border border-brand-200 bg-brand-50 p-4">
                <div class="text-xs font-semibold text-brand-600">TOTAL STOCK MASUK</div>
                <div class="text-2xl font-bold tabular-nums text-brand-800">{{ totals.stock_in.toLocaleString('id-ID') }}</div>
            </div>
            <div class="rounded-2xl border border-brand-300 bg-brand-100 p-4">
                <div class="text-xs font-semibold text-brand-700">TRANSFER KE DISPLAY</div>
                <div class="text-2xl font-bold tabular-nums text-brand-800">{{ totals.transfer.toLocaleString('id-ID') }}</div>
            </div>
            <div class="rounded-2xl border border-brown-200 bg-brown-50 p-4">
                <div class="text-xs font-semibold text-brown-500">TOTAL OUT</div>
                <div class="text-2xl font-bold tabular-nums text-brown-700">{{ totals.out.toLocaleString('id-ID') }}</div>
            </div>
        </div>

        <div class="mt-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <h2 class="mb-3 font-bold text-slate-800">Grafik Stock Movement</h2>
            <MovementChart :labels="series.dates.map(shortDate)" :datasets="datasets" :height="300" />
        </div>

        <div class="mt-4 rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[560px] text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                            <th class="px-4 py-3">Tanggal</th>
                            <th class="px-4 py-3 text-right">Stock Masuk</th>
                            <th class="px-4 py-3 text-right">Transfer ke Display</th>
                            <th class="px-4 py-3 text-right">OUT</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="(d, i) in series.dates" :key="d" class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-medium text-slate-700">{{ d }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ series.stock_in[i] }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ series.transfer[i] }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ series.out[i] }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
