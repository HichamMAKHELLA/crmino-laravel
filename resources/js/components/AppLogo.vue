<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';

const name = usePage().props.name;
</script>

<template>
    <!-- Lettermark CRMino : boîte accent + « C », wordmark à côté (masqué en
         mode réduit). Sur la barre navy, l'accent est le cyan #33B5E6. -->
    <div
        class="bg-sidebar-primary text-sidebar-primary-foreground flex aspect-square size-8 items-center justify-center rounded-md text-base font-black"
    >
        C
    </div>
    <div class="ml-1 grid flex-1 text-left">
        <span class="truncate text-sm leading-tight font-black">{{ name }}</span>
        <span
            class="text-sidebar-foreground/70 truncate text-[10px] leading-tight tracking-wide uppercase"
        >
            IMRASOFT
        </span>
    </div>
</template>
