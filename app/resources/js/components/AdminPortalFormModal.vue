<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

type Portal = {
    id: number;
    display_name: string;
    url: string;
    description: string | null;
    is_active: boolean;
};

const props = defineProps<{
    open: boolean;
    portal?: Portal | null;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const form = useForm({
    display_name: '',
    url: 'https://',
    description: '',
    is_active: true,
});

watch(
    () => props.portal,
    (p) => {
        if (p) {
            form.display_name = p.display_name;
            form.url = p.url;
            form.description = p.description ?? '';
            form.is_active = p.is_active;
        } else {
            form.reset();
        }
    },
    { immediate: true },
);

watch(
    () => props.open,
    (v) => { if (!v) form.clearErrors(); },
);

function submit() {
    if (props.portal) {
        form.put(`/admin-portals/${props.portal.id}`, {
            onSuccess: () => emit('update:open', false),
        });
    } else {
        form.post('/admin-portals', {
            onSuccess: () => { emit('update:open', false); form.reset(); },
        });
    }
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{ portal ? 'Edit portal' : 'Add portal' }}</DialogTitle>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submit">
                <div class="grid gap-1.5">
                    <Label for="portal_name">Display name</Label>
                    <Input id="portal_name" v-model="form.display_name" placeholder="Proxmox VE" />
                    <InputError :message="form.errors.display_name" />
                </div>

                <div class="grid gap-1.5">
                    <Label for="portal_url">URL</Label>
                    <Input id="portal_url" v-model="form.url" type="url" placeholder="https://proxmox.local:8006" />
                    <InputError :message="form.errors.url" />
                </div>

                <div class="grid gap-1.5">
                    <Label for="portal_desc">Description (optional)</Label>
                    <Textarea id="portal_desc" v-model="form.description" placeholder="Proxmox virtualization management" rows="2" />
                    <InputError :message="form.errors.description" />
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="emit('update:open', false)">Cancel</Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ portal ? 'Save changes' : 'Add portal' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
