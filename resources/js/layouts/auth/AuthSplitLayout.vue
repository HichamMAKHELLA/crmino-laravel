<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="relative grid h-dvh flex-col items-center justify-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0"
    >
        <div
            class="relative hidden h-full flex-col p-10 lg:flex dark:border-r"
        >
            <!-- Panneau de marque navy (structure FlowDA). -->
            <div class="bg-sidebar absolute inset-0" />
            <Link
                :href="home()"
                class="relative z-20 flex flex-col gap-1"
                aria-label="CRMino — accueil"
            >
                <span
                    class="text-sidebar-primary text-2xl font-black tracking-tight"
                >
                    CRMino
                </span>
                <span
                    class="text-sidebar-foreground/70 text-xs font-medium tracking-wide uppercase"
                >
                    CRM Commercial &amp; Prospection IMRASOFT
                </span>
            </Link>
        </div>
        <div class="lg:p-8">
            <div
                class="mx-auto flex w-full flex-col justify-center space-y-6 sm:w-[350px]"
            >
                <div class="flex flex-col space-y-2 text-center">
                    <h1 class="text-xl font-medium tracking-tight" v-if="title">
                        {{ title }}
                    </h1>
                    <p class="text-muted-foreground text-sm" v-if="description">
                        {{ description }}
                    </p>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
