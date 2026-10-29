<script setup>
import { onMounted, onUnmounted, ref, watch } from 'vue'
import { Chart, registerables } from 'chart.js'

Chart.register(...registerables)

const props = defineProps({
    labels: { type: Array, required: true },
    datasets: { type: Array, required: true },
    height: { type: Number, default: 260 },
})

const canvas = ref(null)
let chart = null

const render = () => {
    if (!canvas.value) return

    if (chart) {
        chart.destroy()
    }

    chart = new Chart(canvas.value, {
        type: 'line',
        data: {
            labels: props.labels,
            datasets: props.datasets,
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } },
            },
            scales: {
                x: { grid: { display: false } },
                y: { beginAtZero: true, ticks: { precision: 0 } },
            },
        },
    })
}

onMounted(render)
watch(() => [props.labels, props.datasets], render, { deep: true })
onUnmounted(() => chart?.destroy())
</script>

<template>
    <div :style="{ height: height + 'px' }">
        <canvas ref="canvas"></canvas>
    </div>
</template>
