<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import type { BreadcrumbItem } from '@/types';

type SmtpSettings = {
    id?: number;
    host: string;
    port: number;
    username: string | null;
    encryption: string;
    from_address: string;
    from_name: string;
} | null;

const props = defineProps<{ settings: SmtpSettings; status?: string }>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'SMTP settings', href: '/settings/smtp' }];

const form = useForm({
    host: props.settings?.host ?? '',
    port: props.settings?.port ?? 587,
    username: props.settings?.username ?? '',
    password: '',
    encryption: props.settings?.encryption ?? 'tls',
    from_address: props.settings?.from_address ?? '',
    from_name: props.settings?.from_name ?? '',
});

function submit() {
    form.put('/settings/smtp', { preserveScroll: true });
}

const testing = ref(false);
const testResult = ref<{ success: boolean; message: string } | null>(null);

async function testSmtp() {
    testing.value = true;
    testResult.value = null;

    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

    try {
        const response = await fetch('/settings/smtp/test', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify({
                host: form.host,
                port: form.port,
                username: form.username,
                password: form.password,
                encryption: form.encryption,
                from_address: form.from_address,
                from_name: form.from_name,
            }),
        });

        const data = await response.json();
        testResult.value = { success: response.ok, message: data.message };
    } catch {
        testResult.value = { success: false, message: 'Could not reach the server.' };
    } finally {
        testing.value = false;
    }
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="SMTP settings" />
        <h1 class="sr-only">SMTP settings</h1>

        <SettingsLayout>
            <div class="space-y-6">
                <Heading
                    variant="small"
                    title="SMTP configuration"
                    description="Configure the SMTP server used to send alert notifications"
                />

                <form class="space-y-6" @submit.prevent="submit">
                    <div class="grid gap-2">
                        <Label for="smtp_host">SMTP host</Label>
                        <Input id="smtp_host" v-model="form.host" placeholder="smtp.example.com" autocomplete="off" />
                        <InputError :message="form.errors.host" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="smtp_port">Port</Label>
                            <Input id="smtp_port" v-model.number="form.port" type="number" min="1" max="65535" placeholder="587" />
                            <InputError :message="form.errors.port" />
                        </div>
                        <div class="grid gap-2">
                            <Label>Encryption</Label>
                            <Select v-model="form.encryption">
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="tls">TLS (STARTTLS)</SelectItem>
                                    <SelectItem value="ssl">SSL</SelectItem>
                                    <SelectItem value="none">None</SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.encryption" />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="smtp_user">Username</Label>
                        <Input id="smtp_user" v-model="form.username" placeholder="user@example.com" autocomplete="off" />
                        <InputError :message="form.errors.username" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="smtp_pass">Password</Label>
                        <PasswordInput
                            id="smtp_pass"
                            v-model="form.password"
                            autocomplete="new-password"
                            placeholder="Leave blank to keep current password"
                        />
                        <InputError :message="form.errors.password" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="grid gap-2">
                            <Label for="from_addr">From address</Label>
                            <Input id="from_addr" v-model="form.from_address" type="email" placeholder="alerts@example.com" />
                            <InputError :message="form.errors.from_address" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="from_name">From name</Label>
                            <Input id="from_name" v-model="form.from_name" placeholder="OpenHC Alerts" />
                            <InputError :message="form.errors.from_name" />
                        </div>
                    </div>

                    <div class="flex flex-col gap-3">
                        <div class="flex items-center gap-4">
                            <Button type="submit" :disabled="form.processing">Save SMTP settings</Button>
                            <Button type="button" variant="outline" :disabled="testing" @click="testSmtp">
                                {{ testing ? 'Sending…' : 'Send test email' }}
                            </Button>

                            <Transition
                                enter-active-class="transition ease-in-out"
                                enter-from-class="opacity-0"
                                leave-active-class="transition ease-in-out"
                                leave-to-class="opacity-0"
                            >
                                <p v-show="status === 'smtp-saved'" class="text-sm text-neutral-600">Saved.</p>
                            </Transition>
                        </div>

                        <p
                            v-if="testResult"
                            class="text-sm"
                            :class="testResult.success ? 'text-green-600' : 'text-destructive'"
                        >
                            {{ testResult.message }}
                        </p>
                    </div>
                </form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
