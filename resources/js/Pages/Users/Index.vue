<script setup>
import { ref, watch } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import TextInput from '@/Components/TextInput.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import InputError from '@/Components/InputError.vue'
import InputLabel from '@/Components/InputLabel.vue'
import Modal from '@/Components/Modal.vue'
import ConfirmModal from '@/Components/ConfirmModal.vue'

const props = defineProps({
    users: Object,
    filters: Object,
})

const search = ref(props.filters.search ?? '')

watch(search, (s) => {
    router.get(route('users.index'), { search: s }, { preserveState: true, replace: true, debounce: 300 })
})

const modalOpen = ref(false)
const editing = ref(null)

const form = useForm({
    name: '',
    email: '',
    password: '',
    role: 'gudang',
    is_active: true,
})

const openCreate = () => {
    editing.value = null
    form.name = ''
    form.email = ''
    form.password = ''
    form.role = 'gudang'
    form.is_active = true
    form.clearErrors()
    modalOpen.value = true
}

const openEdit = (u) => {
    editing.value = u
    form.name = u.name
    form.email = u.email
    form.password = ''
    form.role = u.role
    form.is_active = u.is_active
    form.clearErrors()
    modalOpen.value = true
}

const save = () => {
    if (editing.value) {
        form.put(route('users.update', editing.value.id), { preserveScroll: true, onSuccess: () => (modalOpen.value = false) })
    } else {
        form.post(route('users.store'), { preserveScroll: true, onSuccess: () => (modalOpen.value = false) })
    }
}

const deleteTarget = ref(null)
const deleteForm = useForm({})
const confirmDelete = () => {
    deleteForm.delete(route('users.destroy', deleteTarget.value.id), {
        preserveScroll: true,
        onSuccess: () => (deleteTarget.value = null),
    })
}
</script>

<template>
    <Head title="Users" />

    <AuthenticatedLayout>
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold text-slate-800 sm:text-2xl">Users</h1>
                <p class="text-sm text-slate-500">Kelola user dan hak akses (Admin / Gudang)</p>
            </div>
            <PrimaryButton @click="openCreate">+ Tambah User</PrimaryButton>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 p-4">
                <TextInput v-model="search" type="text" placeholder="Cari nama atau email..." class="w-full sm:max-w-xs" />
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[640px] text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                            <th class="px-4 py-3">Nama</th>
                            <th class="px-4 py-3">Email</th>
                            <th class="px-4 py-3 text-center">Role</th>
                            <th class="px-4 py-3 text-center">Status</th>
                            <th class="px-4 py-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="u in users.data" :key="u.id" class="hover:bg-slate-50">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-brand-100 text-sm font-bold text-brand-700">{{ u.name.charAt(0) }}</span>
                                    <span class="font-semibold text-slate-800">{{ u.name }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ u.email }}</td>
                            <td class="px-4 py-3 text-center">
                                <span
                                    class="rounded-full px-2.5 py-0.5 text-[11px] font-semibold"
                                    :class="u.role === 'admin' ? 'bg-brown-100 text-brown-700' : 'bg-brand-100 text-brand-700'"
                                >
                                    {{ u.role === 'admin' ? 'ADMIN' : 'GUDANG' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span
                                    class="rounded-full px-2.5 py-0.5 text-[11px] font-semibold"
                                    :class="u.is_active ? 'bg-brand-100 text-brand-700' : 'bg-red-100 text-red-700'"
                                >
                                    {{ u.is_active ? 'ACTIVE' : 'INACTIVE' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-center gap-2">
                                    <button class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-200" @click="openEdit(u)">Edit</button>
                                    <button class="rounded-lg bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-100" @click="deleteTarget = u">Hapus</button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="users.links.length > 3" class="border-t border-slate-100 p-4" v-html="users.links" />
        </div>

        <Modal :show="modalOpen" max-width="lg" @close="modalOpen = false">
            <div class="p-6">
                <h2 class="text-lg font-bold text-slate-800">{{ editing ? 'Edit User' : 'Tambah User' }}</h2>

                <form class="mt-4 space-y-4" @submit.prevent="save">
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <InputLabel for="uname" value="Nama *" />
                            <TextInput id="uname" v-model="form.name" type="text" class="mt-1.5 block w-full" required />
                            <InputError class="mt-2" :message="form.errors.name" />
                        </div>
                        <div>
                            <InputLabel for="uemail" value="Email *" />
                            <TextInput id="uemail" v-model="form.email" type="email" class="mt-1.5 block w-full" required />
                            <InputError class="mt-2" :message="form.errors.email" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <InputLabel for="upass" :value="editing ? 'Password (kosongkan jika tetap)' : 'Password *'" />
                            <TextInput id="upass" v-model="form.password" type="password" class="mt-1.5 block w-full" :required="!editing" />
                            <InputError class="mt-2" :message="form.errors.password" />
                        </div>
                        <div>
                            <InputLabel for="urole" value="Role *" />
                            <select
                                id="urole"
                                v-model="form.role"
                                class="mt-1.5 w-full rounded-lg border-slate-300 focus:border-brand-500 focus:ring-brand-500"
                            >
                                <option value="gudang">Gudang</option>
                                <option value="admin">Admin</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.role" />
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <input id="uactive" v-model="form.is_active" type="checkbox" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                        <label for="uactive" class="text-sm text-slate-700">User aktif</label>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <SecondaryButton type="button" @click="modalOpen = false">Batal</SecondaryButton>
                        <PrimaryButton :disabled="form.processing">{{ editing ? 'Simpan Perubahan' : 'Tambah User' }}</PrimaryButton>
                    </div>
                </form>
            </div>
        </Modal>

        <ConfirmModal
            :show="!!deleteTarget"
            title="Hapus User"
            :message="`Hapus user ${deleteTarget?.name}?`"
            confirm-label="Hapus"
            :busy="deleteForm.processing"
            @close="deleteTarget = null"
            @confirm="confirmDelete"
        />
    </AuthenticatedLayout>
</template>
