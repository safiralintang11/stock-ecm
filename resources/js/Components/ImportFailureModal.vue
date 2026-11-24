<script setup>
import { computed, onMounted, onUnmounted, watch } from 'vue'

const props = defineProps({
    show: { type: Boolean, default: false },
    failures: { type: Array, default: () => [] },
})

const emit = defineEmits(['close'])

const total = computed(() => props.failures.length)

const rowData = (failure) => Object.entries(failure.data ?? {})

const csvCell = (value) => `"${String(value ?? '').replace(/"/g, '""')}"`

const downloadCsv = () => {
    const header = ['Baris', 'Pesan Error', 'Data']
    const lines = props.failures.map((failure) =>
        [
            failure.row,
            failure.message,
            rowData(failure)
                .map(([key, value]) => `${key}=${value}`)
                .join('; '),
        ]
            .map(csvCell)
            .join(','),
    )

    // BOM agar Excel membaca karakter dengan benar.
    const csv = '\uFEFF' + [header.map(csvCell).join(','), ...lines].join('\r\n')
    const stamp = new Date().toISOString().slice(0, 19).replace(/[:T]/g, '-')
    const url = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8;' }))
    const link = document.createElement('a')

    link.href = url
    link.download = `detail-import-gagal-${stamp}.csv`
    document.body.appendChild(link)
    link.click()
    link.remove()
    URL.revokeObjectURL(url)
}

const restoreScroll = () => {
    document.body.style.overflow = ''
}

const onKeydown = (e) => {
    if (e.key === 'Escape' && props.show) {
        emit('close')
    }
}

watch(
    () => props.show,
    (show) => {
        document.body.style.overflow = show ? 'hidden' : ''
    },
    { immediate: true },
)

onMounted(() => document.addEventListener('keydown', onKeydown))

onUnmounted(() => {
    document.removeEventListener('keydown', onKeydown)
    restoreScroll()
})
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 z-[70] flex items-stretch justify-center bg-slate-900/50 sm:items-center sm:p-6"
        @click.self="emit('close')"
    >
        <div
            class="flex h-full w-full flex-col overflow-hidden bg-white shadow-xl sm:h-auto sm:max-h-[85vh] sm:max-w-2xl sm:rounded-xl"
        >
            <div class="flex items-start justify-between gap-3 border-b border-slate-200 px-4 py-3 sm:px-5 sm:py-4">
                <div class="min-w-0">
                    <h2 class="text-base font-semibold text-slate-800">Detail Import Gagal</h2>
                    <p class="mt-1 text-xs text-slate-500">
                        {{ total }} baris tidak masuk. Perbaiki baris berikut lalu import ulang.
                    </p>
                </div>
                <button
                    type="button"
                    class="shrink-0 rounded-lg p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                    aria-label="Tutup"
                    @click="emit('close')"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="min-h-0 flex-1 space-y-2 overflow-y-auto px-4 py-3 sm:px-5">
                <div
                    v-for="failure in failures"
                    :key="failure.row"
                    class="rounded-lg border border-slate-200 bg-slate-50/60 p-3"
                >
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded bg-red-100 px-1.5 py-0.5 text-xs font-semibold text-red-700">
                            Baris {{ failure.row }}
                        </span>
                    </div>
                    <p class="mt-1.5 text-sm break-words whitespace-normal text-slate-700">{{ failure.message }}</p>
                    <div v-if="rowData(failure).length" class="mt-2 flex flex-wrap gap-1">
                        <span
                            v-for="[key, value] in rowData(failure)"
                            :key="key"
                            class="max-w-full truncate rounded bg-white px-1.5 py-0.5 text-xs text-slate-600 ring-1 ring-slate-200"
                            :title="`${key}: ${value}`"
                        >
                            {{ key }}: {{ value }}
                        </span>
                    </div>
                </div>

                <p v-if="!total" class="py-6 text-center text-sm text-slate-500">
                    Tidak ada baris yang gagal.
                </p>
            </div>

            <div class="flex flex-col gap-2 border-t border-slate-200 px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                <p class="hidden text-xs text-slate-500 sm:block">
                    Unduh detail agar bisa dibagikan atau diperbaiki di Excel.
                </p>
                <div class="flex w-full items-center gap-2 sm:w-auto">
                    <button
                        type="button"
                        class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold uppercase tracking-wide text-slate-700 transition hover:bg-slate-50 disabled:opacity-50 sm:flex-none"
                        :disabled="!total"
                        @click="downloadCsv"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0l-4-4m4 4l4-4M4 17v2a2 2 0 002 2h12a2 2 0 002-2v-2" />
                        </svg>
                        Download CSV
                    </button>
                    <button
                        type="button"
                        class="flex-1 rounded-lg bg-brand-600 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-white transition hover:bg-brand-700 sm:flex-none"
                        @click="emit('close')"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
