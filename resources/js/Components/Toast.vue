<script setup>
import { onUnmounted, ref, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import ImportFailureModal from '@/Components/ImportFailureModal.vue'

const page = usePage()

// Flash disalin ke state lokal: page.props di Inertia v2 berupa computed,
// sehingga menulis langsung ke page.props.flash tidak memicu re-render.
const successMessage = ref(null)
const errorMessage = ref(null)
const failures = ref([])
const showFailures = ref(false)

let timer = null

const stopTimer = () => {
    if (timer) {
        clearTimeout(timer)
        timer = null
    }
}

const dismiss = () => {
    stopTimer()
    successMessage.value = null
    errorMessage.value = null
}

const closeFailures = () => {
    showFailures.value = false
    failures.value = []
    dismiss()
}

watch(
    () => page.props,
    (props) => {
        stopTimer()

        const flash = props?.flash ?? {}

        successMessage.value = flash.success ?? null
        errorMessage.value = flash.error ?? null
        failures.value = flash.import_failures ?? []

        if (!successMessage.value && !errorMessage.value) {
            showFailures.value = false
            failures.value = []
            return
        }

        if (failures.value.length > 0) {
            showFailures.value = true
        }

        // Detail tetap tersedia di modal, jadi toast selalu hilang sendiri.
        timer = setTimeout(dismiss, errorMessage.value ? 8000 : 4000)
    },
    { immediate: true },
)

onUnmounted(stopTimer)
</script>

<template>
    <Teleport to="body">
        <div class="pointer-events-none fixed inset-x-4 bottom-4 z-50 flex flex-col items-end gap-2 sm:inset-x-auto sm:right-4 sm:w-full sm:max-w-md">
            <div
                v-if="successMessage"
                class="pointer-events-auto flex w-full items-start gap-2 rounded-xl bg-brand-600 px-4 py-3 text-sm font-medium text-white shadow-lg"
            >
                <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                <p class="min-w-0 flex-1 break-words whitespace-normal">{{ successMessage }}</p>
                <button
                    type="button"
                    class="-mr-1 rounded p-0.5 text-white/80 transition hover:bg-white/10 hover:text-white"
                    aria-label="Tutup notifikasi"
                    @click="dismiss"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div
                v-if="errorMessage"
                class="pointer-events-auto flex w-full items-start gap-2 rounded-xl bg-red-600 px-4 py-3 text-sm font-medium text-white shadow-lg"
            >
                <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
                <div class="min-w-0 flex-1">
                    <p class="max-h-32 overflow-y-auto break-words whitespace-normal">{{ errorMessage }}</p>
                    <button
                        v-if="failures.length"
                        type="button"
                        class="mt-2 rounded-md bg-white/15 px-2 py-1 text-xs font-semibold uppercase tracking-wide transition hover:bg-white/25"
                        @click="showFailures = true"
                    >
                        Lihat detail ({{ failures.length }} baris)
                    </button>
                </div>
                <button
                    type="button"
                    class="-mr-1 rounded p-0.5 text-white/80 transition hover:bg-white/10 hover:text-white"
                    aria-label="Tutup notifikasi"
                    @click="dismiss"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        <ImportFailureModal
            :show="showFailures"
            :failures="failures"
            @close="closeFailures"
        />
    </Teleport>
</template>
