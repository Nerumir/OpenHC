<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { LayoutGrid, LogOut, Settings } from 'lucide-vue-next';
import { ref } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard, logout } from '@/routes';
import { edit as editProfile } from '@/routes/profile';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
];

const showLogoutConfirm = ref(false);

function confirmLogout() {
    router.flushAll();
    router.post(logout());
}
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />

            <SidebarGroup class="px-2 py-0">
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton as-child tooltip="Settings">
                            <Link :href="editProfile()">
                                <Settings />
                                <span>Settings</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                    <SidebarMenuItem>
                        <SidebarMenuButton tooltip="Log out" @click="showLogoutConfirm = true">
                            <LogOut />
                            <span>Log out</span>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarGroup>
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />

    <Dialog v-model:open="showLogoutConfirm">
        <DialogContent class="sm:max-w-sm">
            <DialogHeader>
                <DialogTitle>Log out</DialogTitle>
                <DialogDescription>Are you sure you want to log out?</DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button variant="outline" @click="showLogoutConfirm = false">Cancel</Button>
                <Button variant="destructive" @click="confirmLogout">Log out</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
