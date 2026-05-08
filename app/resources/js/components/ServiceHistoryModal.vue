<script setup lang="ts">
import { onUnmounted, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import type { Chart, ChartData, ChartOptions } from 'chart.js';
import {
    CategoryScale,
    Chart as ChartJS,
    Filler,
    Legend,
    LinearScale,
    LineController,
    LineElement,
    PointElement,
    Title,
    Tooltip,
} from 'chart.js';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { checks as checksRoute } from '@/routes/services';

ChartJS.register(
    CategoryScale,
    LinearScale,
    LineController,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    Filler,
);

type ServiceCheck = {
    status: 'up' | 'down';
    response_time: number | null;
    protocol_detail: string | null;
    checked_at: string;
};

type Service = {
    id: number;
    display_name: string;
    host: string;
    port: number;
    protocol: string;
};

const props = defineProps<{
    open: boolean;
    service: Service | null;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

type Range = '24h' | '7d' | '30d' | 'all';

const rangeOptions: { value: Range; label: string }[] = [
    { value: '24h', label: '24h' },
    { value: '7d', label: '7 days' },
    { value: '30d', label: '30 days' },
    { value: 'all', label: 'All' },
];

const canvasRef = ref<HTMLCanvasElement | null>(null);
const checks = ref<ServiceCheck[]>([]);
const loading = ref(false);
const range = ref<Range>('24h');
let chartInstance: Chart | null = null;

async function fetchChecks(id: number) {
    loading.value = true;
    try {
        const res = await fetch(`${checksRoute.url(id)}?range=${range.value}`, {
            headers: { Accept: 'application/json' },
        });
        checks.value = await res.json();
    } finally {
        loading.value = false;
    }
}

function buildChart() {
    if (!canvasRef.value || checks.value.length === 0) return;

    chartInstance?.destroy();

    const labels = checks.value.map((c) => {
        const d = new Date(c.checked_at);
        const date = d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit' });
        const time = d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
        return `${date} ${time}`;
    });

    const responseTimes = checks.value.map((c) => c.response_time ?? null);
    const pointColors = checks.value.map((c) => (c.status === 'up' ? '#22c55e' : '#ef4444'));

    const data: ChartData<'line'> = {
        labels,
        datasets: [
            {
                label: 'Response time (ms)',
                data: responseTimes,
                borderColor: '#6366f1',
                backgroundColor: 'rgba(99, 102, 241, 0.1)',
                pointBackgroundColor: pointColors,
                pointBorderColor: pointColors,
                pointRadius: 3,
                fill: true,
                tension: 0.3,
                spanGaps: true,
            },
        ],
    };

    const options: ChartOptions<'line'> = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    afterLabel: (ctx) => {
                        const check = checks.value[ctx.dataIndex];
                        return `Status: ${check.status.toUpperCase()}${check.protocol_detail ? ` (${check.protocol_detail})` : ''}`;
                    },
                },
            },
        },
        scales: {
            x: {
                ticks: { maxTicksLimit: 12, maxRotation: 0 },
            },
            y: {
                title: { display: true, text: 'ms' },
                beginAtZero: true,
            },
        },
    };

    chartInstance = new ChartJS(canvasRef.value, { type: 'line', data, options });
}

watch(
    () => [props.open, props.service],
    async ([open, service]) => {
        if (open && service) {
            range.value = '24h';
            await fetchChecks((service as Service).id);
        } else {
            checks.value = [];
            chartInstance?.destroy();
            chartInstance = null;
        }
    },
);

watch(range, async () => {
    if (props.open && props.service) {
        await fetchChecks(props.service.id);
    }
});

watch(checks, () => {
    setTimeout(buildChart, 50);
});

onUnmounted(() => chartInstance?.destroy());

const upCount = () => checks.value.filter((c) => c.status === 'up').length;
const downCount = () => checks.value.filter((c) => c.status === 'down').length;
const avgResponse = () => {
    const valid = checks.value.filter((c) => c.response_time !== null);
    if (!valid.length) return null;
    return Math.round(valid.reduce((s, c) => s + (c.response_time ?? 0), 0) / valid.length);
};
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-3xl">
            <DialogHeader>
                <DialogTitle v-if="service">
                    {{ service.display_name }} — History
                </DialogTitle>
            </DialogHeader>

            <div class="flex gap-1">
                <Button
                    v-for="opt in rangeOptions"
                    :key="opt.value"
                    size="sm"
                    :variant="range === opt.value ? 'default' : 'outline'"
                    @click="range = opt.value"
                >
                    {{ opt.label }}
                </Button>
            </div>

            <div v-if="loading" class="flex items-center justify-center py-16 text-muted-foreground">
                Loading…
            </div>

            <div v-else-if="checks.length === 0" class="py-10 text-center text-sm text-muted-foreground">
                No checks recorded yet.
            </div>

            <div v-else class="space-y-4">
                <div class="flex gap-6 text-sm">
                    <div class="flex items-center gap-1.5">
                        <span class="inline-block h-2.5 w-2.5 rounded-full bg-green-500" />
                        <span>{{ upCount() }} up</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="inline-block h-2.5 w-2.5 rounded-full bg-red-500" />
                        <span>{{ downCount() }} down</span>
                    </div>
                    <div v-if="avgResponse() !== null" class="text-muted-foreground">
                        Avg: {{ avgResponse() }} ms
                    </div>
                </div>

                <div class="relative h-64">
                    <canvas ref="canvasRef" />
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
