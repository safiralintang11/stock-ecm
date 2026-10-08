<script setup>
import { watch } from 'vue'
import { usePage } from '@inertiajs/vue3'

const page = usePage()

// Toast auto-dismiss handled by keying on flash content
watch(
    () => [page.props.flash?.success, page.props.flash?.error],
    () => {
        if (page.props.flash?.success || page.props.flash?.error) {
            setTimeout(() => {
                page.props.flash.success = null
                page.props.flash.error = null
            }, 4000)
        }
    }
)
</script>

<template>
    <Teleport to="body">
        <div class="pointer-events-none fixed bottom-4 right-4 z-50 space-y-2">
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="translate-y-2 opacity-0"
                leave-active-class="transition duration-150 ease-in"
                leave-to-class="translate-y-2 opacity-0"
            >
                <div
                    v-if="page.props.flash?.success"
                    class="pointer-events-auto flex items-center gap-2 rounded-xl bg-brand-600 px-4 py-3 text-sm font-medium text-white shadow-lg"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    {{ page.props.flash.success }}
                </div>
            </Transition>
            <Transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="translate-y-2 opacity-0"
                leave-active-class="transition duration-150 ease-in"
                leave-to-class="translate-y-2 opacity-0"
            >
                <div
                    v-if="page.props.flash?.error"
                    class="pointer-events-auto flex items-center gap-2 rounded-xl bg-red-600 px-4 py-3 text-sm font-medium text-white shadow-lg"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    {{ page.props.flash.error }}
                </div>
            </Transition>
        </div>
    </Teleport>
</template>
