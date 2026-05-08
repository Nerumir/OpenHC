<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import UserInfo from '@/components/UserInfo.vue';
import { SidebarMenu, SidebarMenuItem } from '@/components/ui/sidebar';

const page = usePage();
const user = computed(() => page.props.auth.user);
</script>

<template>
    <SidebarMenu>
        <SidebarMenuItem>
            <div class="flex items-center gap-2 px-2 py-1.5">
                <UserInfo :user="user" :show-email="true" />
            </div>
        </SidebarMenuItem>
    </SidebarMenu>
</template>
