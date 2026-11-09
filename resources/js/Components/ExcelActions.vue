<script setup>
import { computed, ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'

const props = defineProps({
    exportRoute: { type: String, required: true },
    exportParams: { type: Object, default: () => ({}) },
    templateRoute: { type: String, default: '' },
    importRoute: { type: String, default: '' },
    canImport: { type: Boolean, default: false },
})

const page = usePage()
const fileInput = ref(null)
const importing = ref(false)

const fileError = computed(() => page.props.errors?.file ?? '')

const exportUrl = computed(() => route(props.exportRoute, props.exportParams))
const templateUrl = computed(() => (props.templateRoute ? route(props.templateRoute) : ''))

const pickFile = () => fileInput.value?.click()

const onFileChange = (e) => {
    const file = e.target.files?.[0]
    if (!file) return

    importing.value = true
    router.post(route(props.importRoute), { file }, {
        preserveScroll: true,
        onFinish: () => {
            importing.value = false
            if (fileInput.value) fileInput.value.value = ''
        },
    })
}
</script>

<template>
    <div class="flex flex-wrap items-center gap-2">
        <a
            :href="exportUrl"
            class="inline-flex items-center gap-1.5 rounded-lg bg-slate-800 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-white transition hover:bg-slate-700"
        >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0l-4-4m4 4l4-4M4 17v2a2 2 0 002 2h12a2 2 0 002-2v-2" />
            </svg>
            Export Excel
        </a>

        <template v-if="importRoute && canImport">
            <a
                v-if="templateUrl"
                :href="templateUrl"
                class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold uppercase tracking-wide text-slate-700 transition hover:bg-slate-50"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Template
            </a>

            <input
                ref="fileInput"
                type="file"
                accept=".xlsx,.xls,.csv"
                class="hidden"
                @change="onFileChange"
            />
            <button
                type="button"
                :disabled="importing"
                class="inline-flex items-center gap-1.5 rounded-lg bg-brand-600 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-white transition hover:bg-brand-700 disabled:opacity-50"
                @click="pickFile"
            >
                <svg v-if="!importing" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M12 4v12m0 0l-4-4m4 4l4-4" />
                </svg>
                <svg v-else class="h-4 w-4 animate-spin" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h5M20 20v-5h-5M4.5 12a7.5 7.5 0 0112.5-5.3M19.5 12a7.5 7.5 0 01-12.5 5.3" />
                </svg>
                {{ importing ? 'Importing...' : 'Import Excel' }}
            </button>
            <p v-if="fileError" class="w-full text-xs font-medium text-red-600">{{ fileError }}</p>
        </template>
    </div>
</template>