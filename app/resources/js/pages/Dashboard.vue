<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';
import { Edit, History, MonitorCheck, Plus, RefreshCw, Server, Trash2 } from 'lucide-vue-next';
import { PROTOCOL_ICONS } from '@/lib/protocolIcon';
import { computed, ref } from 'vue';
import type { Auth } from '@/types';
import AdminPortalCard from '@/components/AdminPortalCard.vue';
import AdminPortalFormModal from '@/components/AdminPortalFormModal.vue';
import ConfirmDeleteModal from '@/components/ConfirmDeleteModal.vue';
import ServiceFormModal from '@/components/ServiceFormModal.vue';
import ServiceHistoryModal from '@/components/ServiceHistoryModal.vue';
import { Button } from '@/components/ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

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

type Portal = {
    id: number;
    display_name: string;
    url: string;
    description: string | null;
    is_active: boolean;
    last_http_status: number | null;
    last_status: boolean | null;
    last_checked_at: string | null;
};

const props = withDefaults(
    defineProps<{
        services?: Service[];
        portals?: Portal[];
    }>(),
    { services: () => [], portals: () => [] },
);

const page = usePage();
const canEdit = computed(() => {
    const user = (page.props.auth as Auth).user;
    return user.is_admin || user.can_edit;
});

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
];

const activeTab = ref<string>('services');
const showAddService = ref(false);
const showAddPortal = ref(false);
const refreshing = ref(false);

const selectedService = ref<Service | null>(null);
const showEditService = ref(false);
const showHistoryService = ref(false);
const showDeleteService = ref(false);

function openEdit(service: Service) {
    selectedService.value = service;
    showEditService.value = true;
}

function openHistory(service: Service) {
    selectedService.value = service;
    showHistoryService.value = true;
}

function openDelete(service: Service) {
    selectedService.value = service;
    showDeleteService.value = true;
}

function confirmDelete() {
    if (selectedService.value) {
        router.delete(`/services/${selectedService.value.id}`);
    }
}

function checkedAgo(date: string): string {
    const diff = Math.floor((Date.now() - new Date(date).getTime()) / 1000);
    if (diff < 60) return `${diff}s ago`;
    if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
    return `${Math.floor(diff / 3600)}h ago`;
}

function refresh() {
    router.post('/dashboard/refresh', {}, {
        onStart:  () => { refreshing.value = true; },
        onFinish: () => { refreshing.value = false; },
    });
}
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <Tabs v-model="activeTab" class="w-full">
                <div class="flex items-center justify-between">
                    <TabsList>
                        <TabsTrigger value="services" class="gap-2">
                            <Server class="h-4 w-4" />
                            Services
                        </TabsTrigger>
                        <TabsTrigger value="portals" class="gap-2">
                            <MonitorCheck class="h-4 w-4" />
                            Admin Portals
                        </TabsTrigger>
                    </TabsList>

                    <div class="flex items-center gap-2">
                        <Button size="sm" variant="outline" :disabled="refreshing" @click="refresh">
                            <RefreshCw class="h-4 w-4" :class="{ 'animate-spin': refreshing }" />
                        </Button>
                        <Button
                            v-if="canEdit"
                            size="sm"
                            class="gap-1.5"
                            @click="activeTab === 'portals' ? (showAddPortal = true) : (showAddService = true)"
                        >
                            <Plus class="h-4 w-4" />
                            Add {{ activeTab === 'portals' ? 'portal' : 'service' }}
                        </Button>
                    </div>
                </div>

                <TabsContent value="services" class="mt-4">
                    <div v-if="services.length === 0" class="flex flex-col items-center justify-center rounded-xl border border-dashed py-20 text-center">
                        <Server class="mb-3 h-10 w-10 text-muted-foreground/50" />
                        <p class="text-sm font-medium">No services yet</p>
                        <p class="mt-1 text-xs text-muted-foreground">Add a service to start monitoring</p>
                        <Button v-if="canEdit" size="sm" class="mt-4 gap-1.5" @click="showAddService = true">
                            <Plus class="h-4 w-4" />
                            Add service
                        </Button>
                    </div>

                    <div v-else class="flex justify-center overflow-x-auto">
                        <div class="inline-block overflow-hidden rounded-lg border">
                        <table class="text-sm">
                            <thead>
                                <tr class="border-b bg-muted/50">
                                    <th class="px-4 py-3 text-left font-medium text-muted-foreground">State</th>
                                    <th class="px-4 py-3 text-left font-medium text-muted-foreground">Name</th>
                                    <th class="px-4 py-3 text-left font-medium text-muted-foreground">Host</th>
                                    <th class="px-4 py-3 text-left font-medium text-muted-foreground">Protocol</th>
                                    <th class="px-4 py-3 text-left font-medium text-muted-foreground">Info</th>
                                    <th class="px-4 py-3 text-left font-medium text-muted-foreground">Response</th>
                                    <th class="px-4 py-3 text-left font-medium text-muted-foreground">Last check</th>
                                    <th class="px-4 py-3 text-center font-medium text-muted-foreground">History</th>
                                    <th v-if="canEdit" class="px-4 py-3 text-center font-medium text-muted-foreground">Edit</th>
                                    <th v-if="canEdit" class="px-4 py-3 text-center font-medium text-muted-foreground">Remove</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr
                                    v-for="service in services"
                                    :key="service.id"
                                    class="transition-colors even:bg-muted/40 hover:bg-muted/60"
                                >
                                    <td class="px-4 py-3">
                                        <span v-if="service.latest_check?.status === 'up'" class="relative flex h-2.5 w-2.5">
                                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-green-400 opacity-75" />
                                            <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-green-500" />
                                        </span>
                                        <span v-else-if="service.latest_check?.status === 'down'" class="inline-flex h-2.5 w-2.5 rounded-full bg-red-500" />
                                        <span v-else class="inline-flex h-2.5 w-2.5 rounded-full bg-muted-foreground/30" />
                                    </td>

                                    <td class="px-4 py-3 font-medium">{{ service.display_name }}</td>

                                    <td class="px-4 py-3 font-mono text-xs text-muted-foreground">
                                        {{ service.host }}{{ service.port ? ':' + service.port : '' }}
                                    </td>

                                    <td class="px-4 py-3 font-mono text-xs uppercase">
                                        <span class="flex items-center gap-1.5">
                                            <component :is="PROTOCOL_ICONS[service.protocol]" class="h-3.5 w-3.5 shrink-0 text-muted-foreground" />
                                            {{ service.protocol }}
                                        </span>
                                    </td>

                                    <td class="px-4 py-3 font-mono text-xs">
                                        <span v-if="service.latest_check?.protocol_detail">
                                            {{ service.latest_check.protocol_detail }}
                                        </span>
                                        <span v-else class="text-muted-foreground">—</span>
                                    </td>

                                    <td class="px-4 py-3 text-xs text-muted-foreground">
                                        <span v-if="service.latest_check?.response_time != null">
                                            {{ service.latest_check.response_time }} ms
                                        </span>
                                        <span v-else>—</span>
                                    </td>

                                    <td class="px-4 py-3 text-xs text-muted-foreground">
                                        <span v-if="service.latest_check?.checked_at">
                                            {{ checkedAgo(service.latest_check.checked_at) }}
                                        </span>
                                        <span v-else>—</span>
                                    </td>

                                    <td class="px-4 py-3 text-center">
                                        <Button size="sm" variant="ghost" @click="openHistory(service)">
                                            <History class="h-3.5 w-3.5" />
                                        </Button>
                                    </td>

                                    <td v-if="canEdit" class="px-4 py-3 text-center">
                                        <Button size="sm" variant="ghost" @click="openEdit(service)">
                                            <Edit class="h-3.5 w-3.5" />
                                        </Button>
                                    </td>

                                    <td v-if="canEdit" class="px-4 py-3 text-center">
                                        <Button
                                            size="sm"
                                            variant="ghost"
                                            class="text-destructive hover:text-destructive"
                                            @click="openDelete(service)"
                                        >
                                            <Trash2 class="h-3.5 w-3.5" />
                                        </Button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        </div>
                    </div>
                </TabsContent>

                <TabsContent value="portals" class="mt-4">
                    <div v-if="portals.length === 0" class="flex flex-col items-center justify-center rounded-xl border border-dashed py-20 text-center">
                        <MonitorCheck class="mb-3 h-10 w-10 text-muted-foreground/50" />
                        <p class="text-sm font-medium">No admin portals yet</p>
                        <p class="mt-1 text-xs text-muted-foreground">Add a portal to quickly access your admin interfaces</p>
                        <Button v-if="canEdit" size="sm" class="mt-4 gap-1.5" @click="showAddPortal = true">
                            <Plus class="h-4 w-4" />
                            Add portal
                        </Button>
                    </div>

                    <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        <AdminPortalCard
                            v-for="portal in portals"
                            :key="portal.id"
                            :portal="portal"
                        />
                    </div>
                </TabsContent>
            </Tabs>
        </div>
    </AppLayout>

    <ServiceFormModal v-model:open="showAddService" />
    <ServiceFormModal v-model:open="showEditService" :service="selectedService" />
    <ServiceHistoryModal v-model:open="showHistoryService" :service="selectedService" />
    <AdminPortalFormModal v-model:open="showAddPortal" />
    <ConfirmDeleteModal
        v-model:open="showDeleteService"
        title="Delete service"
        :description="`Are you sure you want to delete '${selectedService?.display_name}'?`"
        @confirm="confirmDelete"
    />
</template>
