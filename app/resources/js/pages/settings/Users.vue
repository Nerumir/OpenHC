<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';
import { KeyRound, Plus, Trash2, UserRound } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { ref } from 'vue';
import ConfirmDeleteModal from '@/components/ConfirmDeleteModal.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import type { BreadcrumbItem } from '@/types';

type UserRow = {
    id: number;
    name: string;
    email: string;
    created_at: string;
    is_admin: boolean;
    can_edit: boolean;
};

const props = defineProps<{ users: UserRow[]; status?: string }>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Users', href: '/settings/users' }];

// — Create user ————————————————————————————————————————
const showCreate = ref(false);

const createForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

function submitCreate() {
    createForm.post('/settings/users', {
        preserveScroll: true,
        onSuccess: () => {
            showCreate.value = false;
            createForm.reset();
        },
    });
}

// — Delete user ————————————————————————————————————————
const selectedDelete = ref<UserRow | null>(null);
const showDelete = ref(false);

function toggleEdit(user: UserRow) {
    router.patch(`/settings/users/${user.id}/toggle-edit`, {}, { preserveScroll: true });
}

function openDelete(user: UserRow) {
    selectedDelete.value = user;
    showDelete.value = true;
}

function confirmDelete() {
    if (!selectedDelete.value) return;
    router.delete(`/settings/users/${selectedDelete.value.id}`, { preserveScroll: true });
}

// — Change password ————————————————————————————————————
const selectedUser = ref<UserRow | null>(null);
const showChangePassword = ref(false);

const passwordForm = useForm({
    password: '',
    password_confirmation: '',
});

function openChangePassword(user: UserRow) {
    selectedUser.value = user;
    passwordForm.reset();
    showChangePassword.value = true;
}

function submitPassword() {
    if (!selectedUser.value) return;
    passwordForm.put(`/settings/users/${selectedUser.value.id}/password`, {
        preserveScroll: true,
        onSuccess: () => { showChangePassword.value = false; },
    });
}

function formatDate(iso: string): string {
    return new Date(iso).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Users" />
        <h1 class="sr-only">Users</h1>

        <SettingsLayout>
            <div class="space-y-6">
                <div class="flex items-start justify-between gap-4">
                    <Heading
                        variant="small"
                        title="User management"
                        description="Manage all registered users and their passwords"
                    />
                    <Button size="sm" class="shrink-0 gap-1.5" @click="showCreate = true">
                        <Plus class="h-4 w-4" />
                        Create user
                    </Button>
                </div>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p v-show="status === 'user-created'" class="text-sm text-neutral-600">User created successfully.</p>
                </Transition>

                <div v-if="users.length === 0" class="flex flex-col items-center justify-center rounded-xl border border-dashed py-12 text-center">
                    <UserRound class="mb-3 h-8 w-8 text-muted-foreground/50" />
                    <p class="text-sm text-muted-foreground">No users yet.</p>
                </div>

                <div v-else class="inline-block overflow-hidden rounded-lg border">
                    <table class="text-sm">
                        <thead>
                            <tr class="border-b bg-muted/50">
                                <th class="px-4 py-3 text-left font-medium text-muted-foreground">Name</th>
                                <th class="px-4 py-3 text-left font-medium text-muted-foreground">Email</th>
                                <th class="px-4 py-3 text-left font-medium text-muted-foreground">Registered</th>
                                <th class="px-4 py-3 text-left font-medium text-muted-foreground">Permissions</th>
                                <th class="px-4 py-3 text-center font-medium text-muted-foreground">Password</th>
                                <th class="px-4 py-3 text-center font-medium text-muted-foreground">Remove</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr
                                v-for="user in users"
                                :key="user.id"
                                class="transition-colors even:bg-muted/40 hover:bg-muted/60"
                            >
                                <td class="px-4 py-3 font-medium">{{ user.name }}</td>
                                <td class="px-4 py-3 text-muted-foreground">{{ user.email }}</td>
                                <td class="px-4 py-3 text-xs text-muted-foreground">{{ formatDate(user.created_at) }}</td>
                                <td class="px-4 py-3">
                                    <Badge
                                        v-if="user.is_admin"
                                        variant="outline"
                                        class="text-xs"
                                    >
                                        Admin
                                    </Badge>
                                    <Badge
                                        v-else
                                        :variant="user.can_edit ? 'default' : 'secondary'"
                                        class="cursor-pointer select-none text-xs"
                                        :class="user.can_edit ? 'bg-green-500 hover:bg-green-500/80' : ''"
                                        @click="toggleEdit(user)"
                                    >
                                        {{ user.can_edit ? 'Can edit' : 'Read only' }}
                                    </Badge>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <Button size="sm" variant="ghost" @click="openChangePassword(user)">
                                        <KeyRound class="h-3.5 w-3.5" />
                                    </Button>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <Button
                                        v-if="!user.is_admin"
                                        size="sm"
                                        variant="ghost"
                                        class="text-destructive hover:text-destructive"
                                        @click="openDelete(user)"
                                    >
                                        <Trash2 class="h-3.5 w-3.5" />
                                    </Button>
                                    <span v-else class="text-xs text-muted-foreground/40">—</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>

    <!-- Delete user modal -->
    <ConfirmDeleteModal
        v-model:open="showDelete"
        title="Delete user"
        :description="`Are you sure you want to delete '${selectedDelete?.name}'? This action cannot be undone.`"
        @confirm="confirmDelete"
    />

    <!-- Create user modal -->
    <Dialog v-model:open="showCreate">
        <DialogContent class="sm:max-w-sm">
            <DialogHeader>
                <DialogTitle>Create user</DialogTitle>
                <DialogDescription>Add a new account to this instance.</DialogDescription>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submitCreate">
                <div class="grid gap-2">
                    <Label for="create_name">Name</Label>
                    <Input id="create_name" v-model="createForm.name" autocomplete="off" placeholder="Full name" />
                    <InputError :message="createForm.errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="create_email">Email</Label>
                    <Input id="create_email" v-model="createForm.email" type="email" autocomplete="off" placeholder="user@example.com" />
                    <InputError :message="createForm.errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="create_password">Password</Label>
                    <Input
                        id="create_password"
                        v-model="createForm.password"
                        type="password"
                        autocomplete="new-password"
                        placeholder="Min. 8 characters"
                    />
                    <InputError :message="createForm.errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="create_password_confirmation">Confirm password</Label>
                    <Input
                        id="create_password_confirmation"
                        v-model="createForm.password_confirmation"
                        type="password"
                        autocomplete="new-password"
                    />
                    <InputError :message="createForm.errors.password_confirmation" />
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="showCreate = false">Cancel</Button>
                    <Button type="submit" :disabled="createForm.processing">Create</Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <!-- Change password modal -->
    <Dialog v-model:open="showChangePassword">
        <DialogContent class="sm:max-w-sm">
            <DialogHeader>
                <DialogTitle>Change password</DialogTitle>
                <DialogDescription>
                    Set a new password for <strong>{{ selectedUser?.name }}</strong>.
                </DialogDescription>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submitPassword">
                <div class="grid gap-2">
                    <Label for="new_password">New password</Label>
                    <Input
                        id="new_password"
                        v-model="passwordForm.password"
                        type="password"
                        autocomplete="new-password"
                        placeholder="Min. 8 characters"
                    />
                    <InputError :message="passwordForm.errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="new_password_confirmation">Confirm password</Label>
                    <Input
                        id="new_password_confirmation"
                        v-model="passwordForm.password_confirmation"
                        type="password"
                        autocomplete="new-password"
                    />
                    <InputError :message="passwordForm.errors.password_confirmation" />
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="showChangePassword = false">Cancel</Button>
                    <Button type="submit" :disabled="passwordForm.processing">Save password</Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
