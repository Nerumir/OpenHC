<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
</script>

<template>
    <div
        class="flex aspect-square size-12 items-center justify-center overflow-hidden rounded-2xl bg-sidebar-primary text-sidebar-primary-foreground"
    >
        <AppLogoIcon class="size-12 fill-current text-white dark:text-black" />
    </div>
    <div class="ml-1 flex flex-1 items-baseline gap-1.5 text-left">
        <span class="truncate text-base font-bold leading-tight">OpenHC</span>
        <span class="truncate text-xs text-muted-foreground/70 translate-y-px">by Nerumir</span>
    </div>
</template>
