<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import { PROTOCOL_ICONS } from '@/lib/protocolIcon';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';

type Service = {
    id: number;
    display_name: string;
    host: string;
    port: number | null;
    protocol: string;
    description: string | null;
    is_active: boolean;
};

const props = defineProps<{
    open: boolean;
    service?: Service | null;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const PORT_DEFAULTS: Record<string, number | null> = {
    tcp: 80, http: 80, https: 443, ssh: 22, rdp: 3389, udp: 53,
    database: 3306, ftp: 21, ftps: 990, smtp: 25, smtps: 465,
    icmp: null, irc: 6667, smb: 445, ldap: 389, ldaps: 636,
};

const form = useForm({
    display_name: '',
    host: '',
    port: 80 as number | null,
    protocol: 'tcp',
    description: '',
    is_active: true,
});

const portDisabled = computed(() => form.protocol === 'icmp');

watch(
    () => props.service,
    (s) => {
        if (s) {
            form.display_name = s.display_name;
            form.host = s.host;
            form.port = s.port;
            form.protocol = s.protocol;
            form.description = s.description ?? '';
            form.is_active = s.is_active;
        } else {
            form.reset();
        }
    },
    { immediate: true },
);

watch(
    () => form.protocol,
    (proto) => {
        form.port = PORT_DEFAULTS[proto] ?? null;
    },
);

watch(
    () => props.open,
    (v) => { if (!v) form.clearErrors(); },
);

function submit() {
    if (props.service) {
        form.put(`/services/${props.service.id}`, {
            onSuccess: () => emit('update:open', false),
        });
    } else {
        form.post('/services', {
            onSuccess: () => { emit('update:open', false); form.reset(); },
        });
    }
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{ service ? 'Edit service' : 'Add service' }}</DialogTitle>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submit">
                <div class="grid gap-1.5">
                    <Label for="display_name">Display name</Label>
                    <Input id="display_name" v-model="form.display_name" placeholder="My Web Server" />
                    <InputError :message="form.errors.display_name" />
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div class="col-span-2 grid gap-1.5">
                        <Label for="host">Host / IP</Label>
                        <Input id="host" v-model="form.host" placeholder="192.168.1.1" />
                        <InputError :message="form.errors.host" />
                    </div>
                    <div class="grid gap-1.5">
                        <Label for="port" :class="{ 'text-muted-foreground': portDisabled }">Port</Label>
                        <Input
                            id="port"
                            v-model.number="form.port"
                            type="number"
                            min="1"
                            max="65535"
                            placeholder="N/A"
                            :disabled="portDisabled"
                            :class="{ 'cursor-not-allowed opacity-50': portDisabled }"
                        />
                        <InputError :message="form.errors.port" />
                    </div>
                </div>

                <div class="grid gap-1.5">
                    <Label>Protocol</Label>
                    <div class="flex items-center gap-2">
                    <Select v-model="form.protocol" class="flex-1">
                        <SelectTrigger>
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="tcp">TCP</SelectItem>
                            <SelectItem value="http">HTTP</SelectItem>
                            <SelectItem value="https">HTTPS</SelectItem>
                            <SelectItem value="ssh">SSH</SelectItem>
                            <SelectItem value="rdp">RDP</SelectItem>
                            <SelectItem value="udp">UDP</SelectItem>
                            <SelectItem value="database">DATABASE</SelectItem>
                            <SelectItem value="ftp">FTP</SelectItem>
                            <SelectItem value="ftps">FTPS</SelectItem>
                            <SelectItem value="smtp">SMTP</SelectItem>
                            <SelectItem value="smtps">SMTPS</SelectItem>
                            <SelectItem value="icmp">ICMP</SelectItem>
                            <SelectItem value="irc">IRC</SelectItem>
                            <SelectItem value="smb">SMB</SelectItem>
                            <SelectItem value="ldap">LDAP</SelectItem>
                            <SelectItem value="ldaps">LDAPS</SelectItem>
                        </SelectContent>
                    </Select>
                    <component
                        :is="PROTOCOL_ICONS[form.protocol]"
                        class="h-5 w-5 shrink-0 text-muted-foreground"
                    />
                    </div>
                    <InputError :message="form.errors.protocol" />
                </div>

                <div class="grid gap-1.5">
                    <Label for="description">Description (optional)</Label>
                    <Textarea id="description" v-model="form.description" placeholder="What does this service do?" rows="2" />
                    <InputError :message="form.errors.description" />
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="emit('update:open', false)">Cancel</Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ service ? 'Save changes' : 'Add service' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
