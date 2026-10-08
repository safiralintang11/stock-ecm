<script setup>
import { ref } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import TextInput from '@/Components/TextInput.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import StockFormModal from '@/Components/StockFormModal.vue'

const props = defineProps({
    adjustments: Object,
    products: Array,
    filters: Object,
})

const modalOpen = ref(false)

const filters = useForm({
    search: props.filters.search ?? '',
    scope: props.filters.scope ?? '',
})

const applyFilter = () => {
    router.get(route('stock-adjustments.index'), filters.data(), { preserveState: true, replace: true })
}

const scopeBadge = (scope) => (scope === 'main' ? 'Stock Utama' : 'Stock Display')
</script>

<template>
    <Head title="Stock Adjustment" />

    <AuthenticatedLayout>
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold text-slate-800 sm:text-2xl">Stock Adjustment</h1>
                <p class="text-sm text-slate-500">Koreksi stok dengan alasan wajib (Admin only)</p>
            </div>
            <PrimaryButton @click="modalOpen = true">+ Adjustment</PrimaryButton>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <form class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row" @submit.prevent="applyFilter">
                <TextInput v-model="filters.search" type="text" placeholder="Produk atau SKU..." class="w-full sm:max-w-xs" />
                <select v-model="filters.scope" class="rounded-lg border-slate-300 focus:border-brand-500 focus:ring-brand-500">
                    <option value="">Semua Jenis</option>
                    <option value="display">Stock Display</option>
                    <option value="main">Stock Utama</option>
                </select>
                <SecondaryButton type="submit">Filter</SecondaryButton>
            </form>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                            <th class="px-4 py-3">Tanggal</th>
                            <th class="px-4 py-3">Produk</th>
                            <th class="px-4 py-3 text-center">Scope</th>
                            <th class="px-4 py-3 text-right">Before</th>
                            <th class="px-4 py-3 text-right">Adjust</th>
                            <th class="px-4 py-3 text-right">After</th>
                            <th class="px-4 py-3">Alasan</th>
                            <th class="px-4 py-3 text-center">User</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-if="adjustments.data.length === 0">
                            <td colspan="8" class="px-4 py-10 text-center text-slate-400">Belum ada adjustment</td>
                        </tr>
                        <tr v-for="a in adjustments.data" :key="a.id" class="hover:bg-slate-50">
                            <td class="px-4 py-3 whitespace-nowrap">{{ a.adjust_date }}</td>
                            <td class="px-4 py-3">
                                <div class="font-semibold text-slate-800">{{ a.product.name }}</div>
                                <div class="font-mono text-[11px] text-slate-400">{{ a.product.sku }}</div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600">{{ scopeBadge(a.scope) }}</span>
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums text-slate-500">{{ a.stock_before }}</td>
                            <td
                                class="px-4 py-3 text-right font-bold tabular-nums"
                                :class="a.qty > 0 ? 'text-brand-600' : 'text-red-600'"
                            >
                                {{ a.qty > 0 ? '+' : '' }}{{ a.qty }}
                            </td>
                            <td class="px-4 py-3 text-right font-semibold tabular-nums">{{ a.stock_after }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ a.reason }}</td>
                            <td class="px-4 py-3 text-center text-slate-500">{{ a.user?.name }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="adjustments.links.length > 3" class="border-t border-slate-100 p-4" v-html="adjustments.links" />
        </div>

        <StockFormModal
            :show="modalOpen"
            title="Stock Adjustment"
            :action="route('stock-adjustments.store')"
            :products="products"
            mode="adjustment"
            submit-label="Simpan Adjustment"
            @close="modalOpen = false"
        />
    </AuthenticatedLayout>
</template>
