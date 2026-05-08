<script setup lang="ts">
import { Trash2 } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

defineProps<{
    open: boolean;
    title: string;
    description: string;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    'confirm': [];
}>();
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-sm">
            <DialogHeader>
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-destructive/10">
                        <Trash2 class="h-5 w-5 text-destructive" />
                    </div>
                    <div>
                        <DialogTitle>{{ title }}</DialogTitle>
                        <DialogDescription class="mt-0.5">{{ description }}</DialogDescription>
                    </div>
                </div>
            </DialogHeader>

            <DialogFooter class="mt-2">
                <Button variant="outline" @click="emit('update:open', false)">Cancel</Button>
                <Button
                    variant="destructive"
                    @click="emit('confirm'); emit('update:open', false)"
                >
                    Delete
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
