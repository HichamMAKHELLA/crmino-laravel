<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

interface Critere { code: string; libelle: string; poids: number; source: string | null; evaluable: boolean; manque: string | null }

defineProps<{ total: number; total_atteignable: number; criteres: Critere[] }>();

defineOptions({ layout: { breadcrumbs: [{ title: 'Critères de score', href: '/criteres-score' }] } });
</script>

<template>
    <Head title="Critères de score" />

    <div class="mx-auto flex w-full max-w-3xl flex-col gap-4 p-4">
        <div>
            <h1 class="text-xl font-semibold">Critères de score (§15)</h1>
            <p class="mt-1 text-sm text-muted-foreground">
                Plafond atteignable : <strong>{{ total_atteignable }}</strong> / {{ total }}
                <span v-if="total_atteignable < total" class="text-amber-700 dark:text-amber-400">
                    — des critères sont hors d'atteinte (voir ci-dessous).
                </span>
            </p>
        </div>

        <div class="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left text-muted-foreground">
                    <tr>
                        <th class="px-4 py-2 font-medium">Critère</th>
                        <th class="px-4 py-2 font-medium text-right">Poids</th>
                        <th class="px-4 py-2 font-medium">Source</th>
                        <th class="px-4 py-2 font-medium">État</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="c in criteres" :key="c.code" class="border-t border-sidebar-border/50">
                        <td class="px-4 py-2">{{ c.libelle }}</td>
                        <td class="px-4 py-2 text-right">{{ c.poids }}</td>
                        <td class="px-4 py-2 text-muted-foreground">{{ c.source ?? 'colonne' }}</td>
                        <td class="px-4 py-2">
                            <span v-if="c.evaluable" class="text-xs text-green-700 dark:text-green-300">évaluable</span>
                            <span v-else class="text-xs text-amber-700 dark:text-amber-400">hors d'atteinte — {{ c.manque }}</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
