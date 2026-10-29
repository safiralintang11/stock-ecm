<script setup>
import { ref } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import TextInput from '@/Components/TextInput.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'

const props = defineProps({
    logs: Object,
    users: Array,
    filters: Object,
})

const filters = useForm({
    search: props.filters.search ?? '',
    module: props.filters.module ?? '',
    user_id: props.filters.user_id ?? '',
    from_date: props.filters.from_date ?? '',
    to_date: props.filters.to_date ?? '',
})

const applyFilter = () => {
    router.get(route('audit-logs.index'), filters.data(), { preserveState: true, replace: true })
}

const fmtJson = (json) => (json ? JSON.stringify(json) : '-')

const timeOf = (log) => new Date(log.created_at).toLocaleString('id-ID', {
    day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit',
})
</script>

<template>
    <Head title="Audit Log" />

    <AuthenticatedLayout>
        <div class="mb-5">
            <h1 class="text-xl font-bold text-slate-800 sm:text-2xl">Audit Log</h1>
            <p class="text-sm text-slate-500">Rekam jejak semua perubahan penting</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <form class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row sm:flex-wrap" @submit.prevent="applyFilter">
                <TextInput v-model="filters.search" type="text" placeholder="Action, SKU, alasan..." class="w-full sm:max-w-xs" />
                <select v-model="filters.module" class="rounded-lg border-slate-300 focus:border-brand-500 focus:ring-brand-500">
                    <option value="">Semua Modul</option>
                    <option value="Master Produk">Master Produk</option>
                    <option value="Adjustment">Adjustment</option>
                    <option value="Users">Users</option>
                </select>
                <select v-model="filters.user_id" class="rounded-lg border-slate-300 focus:border-brand-500 focus:ring-brand-500">
                    <option value="">Semua User</option>
                    <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                </select>
                <TextInput v-model="filters.from_date" type="date" class="sm:max-w-[170px]" />
                <TextInput v-model="filters.to_date" type="date" class="sm:max-w-[170px]" />
                <SecondaryButton type="submit">Filter</SecondaryButton>
            </form>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                            <th class="px-4 py-3">Waktu</th>
                            <th class="px-4 py-3 text-center">User</th>
                            <th class="px-4 py-3">Action</th>
                            <th class="px-4 py-3 text-center">Module</th>
                            <th class="px-4 py-3">SKU</th>
                            <th class="px-4 py-3">Before → After</th>
                            <th class="px-4 py-3">Alasan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-if="logs.data.length === 0">
                            <td colspan="7" class="px-4 py-10 text-center text-slate-400">Tidak ada log</td>
                        </tr>
                        <tr v-for="log in logs.data" :key="log.id" class="hover:bg-slate-50">
                            <td class="px-4 py-3 whitespace-nowrap text-slate-600">{{ timeOf(log) }}</td>
                            <td class="px-4 py-3 text-center font-medium text-slate-700">{{ log.user?.name ?? '-' }}</td>
                            <td class="px-4 py-3 font-semibold text-slate-800">{{ log.action }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600">{{ log.module }}</span>
                            </td>
                            <td class="px-4 py-3 font-mono text-xs text-slate-600">{{ log.sku ?? '-' }}</td>
                            <td class="px-4 py-3 max-w-[260px] truncate font-mono text-[11px] text-slate-500" :title="`${fmtJson(log.before)} → ${fmtJson(log.after)}`">
                                {{ fmtJson(log.before) }} → {{ fmtJson(log.after) }}
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ log.reason ?? '-' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="logs.links.length > 3" class="border-t border-slate-100 p-4" v-html="logs.links">
            </div>
        </div>
    </AuthenticatedLayout>
</template>
