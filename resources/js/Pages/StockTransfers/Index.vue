<script setup>
import { ref } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import TextInput from '@/Components/TextInput.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import InputError from '@/Components/InputError.vue'
import InputLabel from '@/Components/InputLabel.vue'
import Modal from '@/Components/Modal.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import ProductSelect from '@/Components/ProductSelect.vue'
import StockFormModal from '@/Components/StockFormModal.vue'

const props = defineProps({
    transfers: Object,
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
    router.get(route('stock-transfers.index'), filters.data(), { preserveState: true, replace: true })
}
</script>

<template>
    <Head title="Stock Transfer" />

    <AuthenticatedLayout>
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold text-slate-800 sm:text-2xl">Stock Transfer</h1>
                <p class="text-sm text-slate-500">Pindah stok dari Stock Utama ke Stock Display</p>
            </div>
            <PrimaryButton @click="modalOpen = true">+ Stock Transfer</PrimaryButton>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <form class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row" @submit.prevent="applyFilter">
                <TextInput v-model="filters.search" type="text" placeholder="No. transfer, produk, SKU..." class="w-full sm:max-w-xs" />
                <TextInput v-model="filters.from_date" type="date" class="sm:max-w-[170px]" />
                <TextInput v-model="filters.to_date" type="date" class="sm:max-w-[170px]" />
                <SecondaryButton type="submit">Filter</SecondaryButton>
            </form>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[820px] text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                            <th class="px-4 py-3">No. Transfer</th>
                            <th class="px-4 py-3">Tanggal</th>
                            <th class="px-4 py-3">Produk</th>
                            <th class="px-4 py-3 text-right">Qty</th>
                            <th class="px-4 py-3 text-center">From → To</th>
                            <th class="px-4 py-3 text-center">User</th>
                            <th class="px-4 py-3">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-if="transfers.data.length === 0">
                            <td colspan="7" class="px-4 py-10 text-center text-slate-400">Belum ada transfer</td>
                        </tr>
                        <tr v-for="t in transfers.data" :key="t.id" class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-mono text-xs font-bold text-slate-700">{{ t.trx_no }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">{{ t.transfer_date }}</td>
                            <td class="px-4 py-3">
                                <div class="font-semibold text-slate-800">{{ t.product.name }}</div>
                                <div class="font-mono text-[11px] text-slate-400">{{ t.product.sku }}</div>
                            </td>
                            <td class="px-4 py-3 text-right font-bold tabular-nums text-brand-700">{{ t.qty }} {{ t.product.unit }}</td>
                            <td class="px-4 py-3 text-center text-xs">
                                <span class="rounded bg-brown-50 px-1.5 py-0.5 font-medium text-brown-600">{{ t.from_location }}</span>
                                <span class="mx-1 text-slate-400">→</span>
                                <span class="rounded bg-brand-50 px-1.5 py-0.5 font-medium text-brand-700">{{ t.to_location }}</span>
                            </td>
                            <td class="px-4 py-3 text-center text-slate-500">{{ t.user?.name }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ t.note ?? '-' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="transfers.links.length > 3" class="border-t border-slate-100 p-4" v-html="transfers.links" />
        </div>

        <StockFormModal
            :show="modalOpen"
            title="Stock Transfer: Utama → Display"
            :action="route('stock-transfers.store')"
            :products="products"
            :balances="balances"
            balance-label="Stock Utama tersedia"
            submit-label="Transfer"
            @close="modalOpen = false"
        />
    </AuthenticatedLayout>
</template>
