<script setup>
import { ref, watch } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import InputError from '@/Components/InputError.vue'
import InputLabel from '@/Components/InputLabel.vue'
import TextInput from '@/Components/TextInput.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import Modal from '@/Components/Modal.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'
import StockBadge from '@/Components/StockBadge.vue'

const props = defineProps({
    products: Object,
    filters: Object,
    canManage: Boolean,
})

const search = ref(props.filters.search ?? '')
const status = ref(props.filters.status ?? '')

watch([search, status], ([s, st]) => {
    router.get(route('products.index'), { search: s, status: st }, { preserveState: true, replace: true })
})

// ---- Create / Edit modal ----
const modalOpen = ref(false)
const editing = ref(null)

const form = useForm({
    name: '',
    sku: '',
    carton_qty: 1,
    unit: 'PCS',
    min_stock: 0,
    status: 'active',
})

const openCreate = () => {
    editing.value = null
    form.name = ''
    form.sku = ''
    form.carton_qty = 1
    form.unit = 'PCS'
    form.min_stock = 0
    form.status = 'active'
    form.clearErrors()
    modalOpen.value = true
}

const openEdit = (p) => {
    editing.value = p
    form.name = p.name
    form.sku = p.sku
    form.carton_qty = p.carton_qty
    form.unit = p.unit
    form.min_stock = p.min_stock
    form.status = p.status
    form.clearErrors()
    modalOpen.value = true
}

const save = () => {
    if (editing.value) {
        form.put(route('products.update', editing.value.id), { preserveScroll: true, onSuccess: () => (modalOpen.value = false) })
    } else {
        form.post(route('products.store'), { preserveScroll: true, onSuccess: () => (modalOpen.value = false) })
    }
}

// ---- Delete ----
const deleteTarget = ref(null)
const deleteForm = useForm({})
const confirmDelete = () => {
    deleteForm.delete(route('products.destroy', deleteTarget.value.id), {
        preserveScroll: true,
        onSuccess: () => (deleteTarget.value = null),
    })
}

const displayQty = (p) => p.display_qty ?? 0
const stockStatus = (p) => {
    const d = displayQty(p)
    return d <= 0 ? 'OUT_OF_STOCK' : d <= p.min_stock ? 'LOW_STOCK' : 'OK'
}
</script>

<template>
    <Head title="Master Produk" />

    <AuthenticatedLayout>
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold text-slate-800 sm:text-2xl">Master Produk</h1>
                <p class="text-sm text-slate-500">Kelola data produk, SKU, isi carton, dan minimum stock</p>
            </div>
            <PrimaryButton v-if="canManage" @click="openCreate">+ Tambah Produk</PrimaryButton>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row">
                <TextInput
                    v-model="search"
                    type="text"
                    placeholder="Cari nama produk atau SKU..."
                    class="w-full sm:max-w-xs"
                />
                <select v-model="status" class="rounded-lg border-slate-300 focus:border-brand-500 focus:ring-brand-500">
                    <option value="">Semua Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                            <th class="px-4 py-3">Nama Produk</th>
                            <th class="px-4 py-3">SKU</th>
                            <th class="px-4 py-3 text-center">Isi Carton</th>
                            <th class="px-4 py-3 text-center">Satuan</th>
                            <th class="px-4 py-3 text-right">Min. Stock</th>
                            <th class="px-4 py-3 text-right">Stock Utama</th>
                            <th class="px-4 py-3 text-right">Stock Display</th>
                            <th class="px-4 py-3 text-center">Status</th>
                            <th v-if="canManage" class="px-4 py-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-if="products.data.length === 0">
                            <td colspan="9" class="px-4 py-10 text-center text-slate-400">Belum ada produk</td>
                        </tr>
                        <tr v-for="p in products.data" :key="p.id" class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-semibold text-slate-800">{{ p.name }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded bg-slate-100 px-2 py-0.5 font-mono text-xs font-semibold text-slate-600">{{ p.sku }}</span>
                            </td>
                            <td class="px-4 py-3 text-center tabular-nums">{{ p.carton_qty }}</td>
                            <td class="px-4 py-3 text-center">{{ p.unit }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ p.min_stock }}</td>
                            <td class="px-4 py-3 text-right font-semibold tabular-nums">{{ p.main_qty ?? 0 }}</td>
                            <td class="px-4 py-3 text-right">
                                <span class="font-semibold tabular-nums">{{ displayQty(p) }}</span>
                                <StockBadge v-if="p.status === 'active'" :status="stockStatus(p)" class="ml-2" />
                            </td>
                            <td class="px-4 py-3 text-center"><StockBadge :status="p.status" /></td>
                            <td v-if="canManage" class="px-4 py-3">
                                <div class="flex justify-center gap-2">
                                    <button class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-200" @click="openEdit(p)">Edit</button>
                                    <button class="rounded-lg bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-100" @click="deleteTarget = p">Hapus</button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="products.links.length > 3" class="border-t border-slate-100 p-4" v-html="products.links" />
        </div>

        <!-- Create / Edit modal -->
        <Modal :show="modalOpen" max-width="lg" @close="modalOpen = false">
            <div class="p-6">
                <h2 class="text-lg font-bold text-slate-800">{{ editing ? 'Edit Produk' : 'Tambah Produk' }}</h2>

                <form class="mt-4 space-y-4" @submit.prevent="save">
                    <div>
                        <InputLabel for="name" value="Nama Produk *" />
                        <TextInput id="name" v-model="form.name" type="text" class="mt-1.5 block w-full" required />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <InputLabel for="sku" value="SKU *" />
                            <TextInput id="sku" v-model="form.sku" type="text" class="mt-1.5 block w-full" placeholder="contoh: PRD001" required />
                            <InputError class="mt-2" :message="form.errors.sku" />
                        </div>
                        <div>
                            <InputLabel for="unit" value="Satuan *" />
                            <TextInput id="unit" v-model="form.unit" type="text" class="mt-1.5 block w-full" required />
                            <InputError class="mt-2" :message="form.errors.unit" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div>
                            <InputLabel for="carton_qty" value="Isi dalam 1 Carton *" />
                            <TextInput id="carton_qty" v-model="form.carton_qty" type="number" min="1" class="mt-1.5 block w-full" required />
                            <InputError class="mt-2" :message="form.errors.carton_qty" />
                        </div>
                        <div>
                            <InputLabel for="min_stock" value="Minimum Stock *" />
                            <TextInput id="min_stock" v-model="form.min_stock" type="number" min="0" class="mt-1.5 block w-full" required />
                            <InputError class="mt-2" :message="form.errors.min_stock" />
                        </div>
                        <div>
                            <InputLabel for="pstatus" value="Status *" />
                            <select
                                id="pstatus"
                                v-model="form.status"
                                class="mt-1.5 w-full rounded-lg border-slate-300 focus:border-brand-500 focus:ring-brand-500"
                            >
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.status" />
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <SecondaryButton type="button" @click="modalOpen = false">Batal</SecondaryButton>
                        <PrimaryButton :disabled="form.processing">{{ editing ? 'Simpan Perubahan' : 'Tambah Produk' }}</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Delete confirm -->
        <ConfirmModal
            :show="!!deleteTarget"
            title="Hapus Produk"
            :message="`Hapus produk ${deleteTarget?.name} (${deleteTarget?.sku})? Produk yang sudah dipakai dalam transaksi tidak dapat dihapus.`"
            confirm-label="Hapus"
            :busy="deleteForm.processing"
            @close="deleteTarget = null"
            @confirm="confirmDelete"
        />
    </AuthenticatedLayout>
</template>
