<script setup>
import { ref } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import TextInput from '@/Components/TextInput.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import StockFormModal from '@/Components/StockFormModal.vue'

const props = defineProps({
    outs: Object,
    products: Array,
    balances: Object,
    filters: Object,
})

const modalOpen = ref(false)

const filters = useForm({
    search: props.filters.search ?? '',
    from_date: props.filters.from_date ?? '',
    to_date: props.filters.to_date ?? '',
})

const applyFilter = () => {
    router.get(route('stock-outs.index'), filters.data(), { preserveState: true, replace: true })
}

const balanceFor = (id) => props.balances?.[id] ?? null
</script>

<template>
    <Head title="Stock OUT" />

    <AuthenticatedLayout>
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold text-slate-800 sm:text-2xl">OUT / Stock Keluar</h1>
                <p class="text-sm text-slate-500">Pengeluaran harian dari Stock Display (tidak mengurangi Stock Utama langsung)</p>
            </div>
            <PrimaryButton @click="modalOpen = true">+ Catat OUT</PrimaryButton>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <form class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row" @submit.prevent="applyFilter">
                <TextInput v-model="filters.search" type="text" placeholder="No. transaksi, produk, SKU..." class="w-full sm:max-w-xs" />
                <TextInput v-model="filters.from_date" type="date" class="sm:max-w-[170px]" />
                <TextInput v-model="filters.to_date" type="date" class="sm:max-w-[170px]" />
                <SecondaryButton type="submit">Filter</SecondaryButton>
            </form>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[780px] text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                            <th class="px-4 py-3">No. Transaksi</th>
                            <th class="px-4 py-3">Tanggal</th>
                            <th class="px-4 py-3">Produk</th>
                            <th class="px-4 py-3 text-right">Qty Keluar</th>
                            <th class="px-4 py-3 text-center">User</th>
                            <th class="px-4 py-3">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-if="outs.data.length === 0">
                            <td colspan="6" class="px-4 py-10 text-center text-slate-400">Belum ada transaksi OUT</td>
                        </tr>
                        <tr v-for="o in outs.data" :key="o.id" class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-mono text-xs font-bold text-slate-700">{{ o.trx_no }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">{{ o.out_date }}</td>
                            <td class="px-4 py-3">
                                <div class="font-semibold text-slate-800">{{ o.product.name }}</div>
                                <div class="font-mono text-[11px] text-slate-400">{{ o.product.sku }}</div>
                            </td>
                            <td class="px-4 py-3 text-right font-bold tabular-nums text-red-600">-{{ o.qty }} {{ o.product.unit }}</td>
                            <td class="px-4 py-3 text-center text-slate-500">{{ o.user?.name }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ o.note ?? '-' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="outs.links.length > 3" class="border-t border-slate-100 p-4" v-html="outs.links" />
        </div>

        <StockFormModal
            :show="modalOpen"
            title="Catat OUT dari Stock Display"
            :action="route('stock-outs.store')"
            :products="products"
            :balances="balances"
            balance-label="Stock Display tersedia"
            submit-label="Catat OUT"
            @close="modalOpen = false"
        />
    </AuthenticatedLayout>
</template>
