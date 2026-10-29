<script setup>
import { ref, watch } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import TextInput from '@/Components/TextInput.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'

const props = defineProps({
    histories: Object,
    filters: Object,
    types: Array,
})

const filters = ref({
    search: props.filters.search ?? '',
    type: props.filters.type ?? '',
    from_date: props.filters.from_date ?? '',
    to_date: props.filters.to_date ?? '',
})

const applyFilter = () => {
    router.get(route('reports.stock-history'), filters.value, { preserveState: true, replace: true })
}

const typeColor = (type) => {
    switch (type) {
        case 'INITIAL_STOCK': return 'bg-brown-100 text-brown-700'
        case 'STOCK_IN': return 'bg-brand-100 text-brand-700'
        case 'STOCK_TRANSFER': return 'bg-brand-200 text-brand-800'
        case 'STOCK_DISPLAY': return 'bg-cream text-brown-600'
        case 'OUT': return 'bg-red-100 text-red-700'
        case 'ADJUSTMENT': return 'bg-accent-100 text-accent-700'
        default: return 'bg-slate-100 text-slate-700'
    }
}

const timeOf = (h) => new Date(h.created_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
</script>

<template>
    <Head title="Stock History" />

    <AuthenticatedLayout>
        <div class="mb-5">
            <h1 class="text-xl font-bold text-slate-800 sm:text-2xl">Stock History</h1>
            <p class="text-sm text-slate-500">Semua perubahan stok tercatat di sini (read-only)</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <form class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row sm:flex-wrap" @submit.prevent="applyFilter">
                <TextInput v-model="filters.search" type="text" placeholder="Produk, SKU, no. referensi..." class="w-full sm:max-w-xs" />
                <select v-model="filters.type" class="rounded-lg border-slate-300 focus:border-brand-500 focus:ring-brand-500">
                    <option value="">Semua Jenis</option>
                    <option v-for="t in types" :key="t" :value="t">{{ t.replaceAll('_', ' ') }}</option>
                </select>
                <TextInput v-model="filters.from_date" type="date" class="sm:max-w-[170px]" />
                <TextInput v-model="filters.to_date" type="date" class="sm:max-w-[170px]" />
                <SecondaryButton type="submit">Filter</SecondaryButton>
            </form>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[980px] text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                            <th class="px-4 py-3">Tanggal / Waktu</th>
                            <th class="px-4 py-3">Produk</th>
                            <th class="px-4 py-3 text-center">Jenis</th>
                            <th class="px-4 py-3 text-right">Qty</th>
                            <th class="px-4 py-3 text-right">Utama (→)</th>
                            <th class="px-4 py-3 text-right">Display (→)</th>
                            <th class="px-4 py-3 text-center">User</th>
                            <th class="px-4 py-3">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-if="histories.data.length === 0">
                            <td colspan="8" class="px-4 py-10 text-center text-slate-400">Tidak ada histori</td>
                        </tr>
                        <tr v-for="h in histories.data" :key="h.id" class="hover:bg-slate-50">
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="font-medium text-slate-700">{{ h.trx_date }}</div>
                                <div class="text-[11px] text-slate-400">{{ timeOf(h) }} WIB</div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-semibold text-slate-800">{{ h.product.name }}</div>
                                <div class="font-mono text-[11px] text-slate-400">{{ h.product.sku }}</div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold" :class="typeColor(h.type)">
                                    {{ h.type.replaceAll('_', ' ') }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right font-bold tabular-nums">{{ h.qty }} {{ h.product.unit }}</td>
                            <td class="px-4 py-3 text-right tabular-nums text-slate-500">{{ h.main_before }} → <span class="font-semibold text-slate-700">{{ h.main_after }}</span></td>
                            <td class="px-4 py-3 text-right tabular-nums text-slate-500">{{ h.display_before }} → <span class="font-semibold text-slate-700">{{ h.display_after }}</span></td>
                            <td class="px-4 py-3 text-center text-slate-500">{{ h.user?.name }}</td>
                            <td class="px-4 py-3 text-slate-500">
                                <div>{{ h.description ?? '-' }}</div>
                                <div v-if="h.reference" class="font-mono text-[11px] text-slate-400">{{ h.reference }}</div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="histories.links.length > 3" class="border-t border-slate-100 p-4" v-html="histories.links" />
        </div>
    </AuthenticatedLayout>
</template>
