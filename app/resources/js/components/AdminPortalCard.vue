<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { Edit, ExternalLink, ImageOff, RefreshCw, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import type { Auth } from '@/types';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import AdminPortalFormModal from '@/components/AdminPortalFormModal.vue';
import ConfirmDeleteModal from '@/components/ConfirmDeleteModal.vue';

type Portal = {
    id: number;
    display_name: string;
    url: string;
    description: string | null;
    is_active: boolean;
    last_http_status: number | null;
    last_status: boolean | null;
    last_checked_at: string | null;
    screenshot_path: string | null;
};

const props = defineProps<{ portal: Portal }>();

const page = usePage();
const canEdit = computed(() => {
    const user = (page.props.auth as Auth).user;
    return user.is_admin || user.can_edit;
});

const showEdit = ref(false);
const showDelete = ref(false);
const imgError = ref(false);

function confirmDelete() {
    router.delete(`/admin-portals/${props.portal.id}`);
}

function openPortal() {
    window.open(props.portal.url, '_blank', 'noopener,noreferrer');
}

function refreshScreenshot() {
    router.post(`/admin-portals/${props.portal.id}/refresh-screenshot`, {}, { preserveScroll: true });
}

const hostname = (url: string) => {
    try { return new URL(url).hostname; } catch { return url; }
};
</script>

<template>
    <Card class="flex flex-col transition-shadow hover:shadow-md">
        <CardHeader class="flex flex-row items-start justify-between gap-2 pb-2">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                    <img
                        :src="`/favicon-proxy?url=${encodeURIComponent(portal.url)}`"
                        class="h-4 w-4 shrink-0"
                        alt=""
                        loading="lazy"
                        @error="($event.target as HTMLImageElement).style.display = 'none'"
                    />
                    <h3 class="font-semibold truncate">{{ portal.display_name }}</h3>
                </div>
                <p class="text-xs text-muted-foreground mt-0.5 truncate">{{ portal.url }}</p>
            </div>

            <div class="flex items-center gap-1 shrink-0">
                <Badge
                    v-if="portal.last_status !== null"
                    :variant="portal.last_status ? 'default' : 'destructive'"
                    class="text-xs"
                    :class="portal.last_status ? 'bg-green-500 hover:bg-green-500/80' : ''"
                >
                    {{ portal.last_status ? 'UP' : 'DOWN' }}
                </Badge>
                <Badge v-if="portal.last_http_status" variant="outline" class="text-xs font-mono">
                    {{ portal.last_http_status }}
                </Badge>
                <Badge v-else-if="portal.last_status === null" variant="secondary" class="text-xs">
                    PENDING
                </Badge>
            </div>
        </CardHeader>

        <CardContent class="flex flex-1 flex-col gap-3 pt-0">
            <div v-if="portal.description" class="text-sm text-muted-foreground line-clamp-2">
                {{ portal.description }}
            </div>

            <div
                class="relative h-72 overflow-hidden rounded border bg-muted/30 cursor-pointer"
                @click="openPortal"
            >
                <img
                    v-if="portal.screenshot_path && portal.screenshot_path !== 'pending' && portal.screenshot_path !== 'failed' && !imgError"
                    :src="`/storage/${portal.screenshot_path}`"
                    class="w-full h-full object-cover object-top transition-transform duration-200 hover:scale-105"
                    alt="Portal preview"
                    @error="imgError = true"
                />
                <div
                    v-else-if="portal.screenshot_path === 'pending' || portal.screenshot_path === null"
                    class="flex h-full flex-col items-center justify-center gap-1 text-muted-foreground"
                >
                    <RefreshCw class="h-5 w-5 animate-spin opacity-50" />
                    <span class="text-xs">Generating preview…</span>
                </div>
                <div
                    v-else
                    class="flex h-full flex-col items-center justify-center gap-1 text-muted-foreground"
                >
                    <ImageOff class="h-5 w-5 opacity-50" />
                    <span class="text-xs">Preview unavailable — click ↺ to retry</span>
                </div>
            </div>

            <div class="flex gap-2">
                <Button size="sm" class="flex-1 gap-1.5" @click="openPortal">
                    <ExternalLink class="h-3.5 w-3.5" />
                    Open
                </Button>
                <Button v-if="canEdit" size="sm" variant="ghost" title="Refresh preview" @click="refreshScreenshot">
                    <RefreshCw class="h-3.5 w-3.5" />
                </Button>
                <Button v-if="canEdit" size="sm" variant="ghost" @click="showEdit = true">
                    <Edit class="h-3.5 w-3.5" />
                </Button>
                <Button v-if="canEdit" size="sm" variant="ghost" class="text-destructive hover:text-destructive" @click="showDelete = true">
                    <Trash2 class="h-3.5 w-3.5" />
                </Button>
            </div>
        </CardContent>
    </Card>

    <AdminPortalFormModal v-model:open="showEdit" :portal="portal" />
    <ConfirmDeleteModal
        v-model:open="showDelete"
        title="Delete portal"
        :description="`Are you sure you want to delete '${portal.display_name}'?`"
        @confirm="confirmDelete"
    />
</template>
