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
        class="bg-background flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10"
    >
        <div class="w-full max-w-sm">
            <div class="flex flex-col gap-8">
                <div class="flex flex-col items-center gap-4">
                    <!-- Marque CRMino + sous-titre §82. -->
                    <Link
                        :href="home()"
                        class="flex flex-col items-center gap-1"
                        aria-label="CRMino — accueil"
                    >
                        <span
                            class="text-brand text-3xl font-black tracking-tight"
                        >
                            CRMino
                        </span>
                        <span
                            class="text-muted-foreground text-xs font-medium tracking-wide uppercase"
                        >
                            CRM Commercial &amp; Prospection IMRASOFT
                        </span>
                    </Link>
                    <div class="space-y-2 text-center">
                        <h1 class="text-xl font-bold">{{ title }}</h1>
                        <p class="text-muted-foreground text-center text-sm">
                            {{ description }}
                        </p>
                    </div>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
