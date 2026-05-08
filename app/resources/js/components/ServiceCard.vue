<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Activity, Clock, Edit, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import ServiceFormModal from '@/components/ServiceFormModal.vue';
import ServiceHistoryModal from '@/components/ServiceHistoryModal.vue';

type LatestCheck = {
    status: 'up' | 'down';
    response_time: number | null;
    protocol_detail: string | null;
    checked_at: string;
} | null;

type Service = {
    id: number;
    display_name: string;
    host: string;
    port: number | null;
    protocol: string;
    description: string | null;
    is_active: boolean;
    latest_check: LatestCheck;
};

const props = defineProps<{ service: Service }>();

const showHistory = ref(false);
const showEdit = ref(false);

function destroy() {
    if (!confirm(`Delete "${props.service.display_name}"?`)) return;
    router.delete(`/services/${props.service.id}`);
}

function checkedAgo(date: string) {
    const diff = Math.floor((Date.now() - new Date(date).getTime()) / 1000);
    if (diff < 60) return `${diff}s ago`;
    if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
    return `${Math.floor(diff / 3600)}h ago`;
}
</script>

<template>
    <Card class="flex flex-col transition-shadow hover:shadow-md">
        <CardHeader class="flex flex-row items-start justify-between gap-2 pb-2">
            <div class="flex-1 min-w-0">
                <h3 class="font-semibold truncate">{{ service.display_name }}</h3>
                <p class="text-xs text-muted-foreground">
                    {{ service.protocol.toUpperCase() }} · {{ service.host }}{{ service.port ? ':' + service.port : '' }}
                </p>
            </div>

            <div class="flex items-center gap-1 shrink-0">
                <Badge
                    v-if="service.latest_check"
                    :variant="service.latest_check.status === 'up' ? 'default' : 'destructive'"
                    class="text-xs"
                    :class="service.latest_check.status === 'up' ? 'bg-green-500 hover:bg-green-500/80' : ''"
                >
                    {{ service.latest_check.status.toUpperCase() }}
                </Badge>
                <Badge v-else variant="secondary" class="text-xs">PENDING</Badge>
                <Badge
                    v-if="service.latest_check?.protocol_detail"
                    variant="outline"
                    class="text-xs font-mono"
                >
                    {{ service.latest_check.protocol_detail }}
                </Badge>
            </div>
        </CardHeader>

        <CardContent class="flex flex-1 flex-col justify-between gap-3 pt-0">
            <div v-if="service.description" class="text-sm text-muted-foreground line-clamp-2">
                {{ service.description }}
            </div>

            <div class="space-y-1 text-xs text-muted-foreground">
                <div v-if="service.latest_check?.response_time" class="flex items-center gap-1">
                    <Clock class="h-3 w-3" />
                    {{ service.latest_check.response_time }} ms
                </div>
                <div v-if="service.latest_check?.checked_at" class="flex items-center gap-1">
                    <Activity class="h-3 w-3" />
                    {{ checkedAgo(service.latest_check.checked_at) }}
                </div>
            </div>

            <div class="flex gap-2 pt-1">
                <Button size="sm" variant="outline" class="flex-1" @click="showHistory = true">
                    History
                </Button>
                <Button size="sm" variant="ghost" @click="showEdit = true">
                    <Edit class="h-3.5 w-3.5" />
                </Button>
                <Button size="sm" variant="ghost" class="text-destructive hover:text-destructive" @click="destroy">
                    <Trash2 class="h-3.5 w-3.5" />
                </Button>
            </div>
        </CardContent>
    </Card>

    <ServiceHistoryModal v-model:open="showHistory" :service="service" />
    <ServiceFormModal v-model:open="showEdit" :service="service" />
</template>
