<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';
import { Mail, Trash2 } from 'lucide-vue-next';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import type { BreadcrumbItem } from '@/types';

type NotificationEmail = {
    id: number;
    email: string;
    display_name: string | null;
    is_active: boolean;
};

const intervalOptions = [
    { value: 5,    label: 'Every 5 minutes' },
    { value: 10,   label: 'Every 10 minutes' },
    { value: 15,   label: 'Every 15 minutes' },
    { value: 30,   label: 'Every 30 minutes' },
    { value: 60,   label: 'Every hour' },
    { value: 120,  label: 'Every 2 hours' },
    { value: 240,  label: 'Every 4 hours' },
    { value: 480,  label: 'Every 8 hours' },
    { value: 1440, label: 'Every 24 hours' },
];

const props = defineProps<{
    emails: NotificationEmail[];
    notification_interval_minutes: number;
    status?: string;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Notification emails', href: '/settings/notifications' },
];

const form = useForm({ email: '', display_name: '' });

const intervalForm = useForm({
    notification_interval_minutes: props.notification_interval_minutes,
});

function saveInterval() {
    intervalForm.patch('/settings/notifications/interval', { preserveScroll: true });
}

function add() {
    form.post('/settings/notifications', {
        onSuccess: () => form.reset(),
        preserveScroll: true,
    });
}

function remove(id: number) {
    if (!confirm('Remove this email?')) return;
    router.delete(`/settings/notifications/${id}`, { preserveScroll: true });
}

function toggle(id: number) {
    router.patch(`/settings/notifications/${id}/toggle`, {}, { preserveScroll: true });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Notification emails" />
        <h1 class="sr-only">Notification emails</h1>

        <SettingsLayout>
            <div class="space-y-6">
                <Heading
                    variant="small"
                    title="Notification frequency"
                    description="How often to re-send alerts while at least one service is down"
                />

                <form class="flex items-end gap-4" @submit.prevent="saveInterval">
                    <div class="grid gap-2">
                        <Label>Alert interval</Label>
                        <Select v-model.number="intervalForm.notification_interval_minutes">
                            <SelectTrigger class="w-52">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="opt in intervalOptions"
                                    :key="opt.value"
                                    :value="opt.value"
                                >
                                    {{ opt.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div class="flex items-center gap-4">
                        <Button type="submit" size="sm" :disabled="intervalForm.processing">Save</Button>
                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p v-show="status === 'interval-saved'" class="text-sm text-neutral-600">Saved.</p>
                        </Transition>
                    </div>
                </form>

                <Heading
                    variant="small"
                    title="Alert recipients"
                    description="Emails that receive alerts when services go down"
                />

                <form class="space-y-3" @submit.prevent="add">
                    <div class="flex gap-3">
                        <div class="flex-1 grid gap-1.5">
                            <Label for="notif_email">Email address</Label>
                            <Input id="notif_email" v-model="form.email" type="email" placeholder="admin@example.com" />
                            <InputError :message="form.errors.email" />
                        </div>
                        <div class="flex-1 grid gap-1.5">
                            <Label for="notif_name">Name (optional)</Label>
                            <Input id="notif_name" v-model="form.display_name" placeholder="IT Admin" />
                        </div>
                    </div>
                    <Button type="submit" size="sm" :disabled="form.processing">Add recipient</Button>
                </form>

                <div v-if="emails.length === 0" class="rounded-lg border border-dashed p-6 text-center">
                    <Mail class="mx-auto mb-2 h-8 w-8 text-muted-foreground/50" />
                    <p class="text-sm text-muted-foreground">No recipients configured yet.</p>
                </div>

                <ul v-else class="divide-y rounded-lg border">
                    <li
                        v-for="item in emails"
                        :key="item.id"
                        class="flex items-center justify-between gap-3 px-4 py-3"
                    >
                        <div class="flex items-center gap-3 min-w-0">
                            <Mail class="h-4 w-4 shrink-0 text-muted-foreground" />
                            <div class="min-w-0">
                                <p class="text-sm font-medium truncate">{{ item.email }}</p>
                                <p v-if="item.display_name" class="text-xs text-muted-foreground">{{ item.display_name }}</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            <Badge
                                :variant="item.is_active ? 'default' : 'secondary'"
                                class="cursor-pointer select-none text-xs"
                                :class="item.is_active ? 'bg-green-500 hover:bg-green-500/80' : ''"
                                @click="toggle(item.id)"
                            >
                                {{ item.is_active ? 'Active' : 'Paused' }}
                            </Badge>
                            <Button
                                size="sm"
                                variant="ghost"
                                class="text-destructive hover:text-destructive"
                                @click="remove(item.id)"
                            >
                                <Trash2 class="h-3.5 w-3.5" />
                            </Button>
                        </div>
                    </li>
                </ul>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
