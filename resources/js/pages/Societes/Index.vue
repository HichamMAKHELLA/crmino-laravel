<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

interface LigneSociete {
    id: number;
    numero: string;
    raison_sociale: string | null;
    etat: string;
    ville: string | null;
    proprietaire: string | null;
}

defineProps<{ societes: { data: LigneSociete[]; total: number } }>();

const teinteEtat: Record<string, string> = {
    Prospect: 'bg-primary/10 text-primary',
    Client: 'bg-green-600/15 text-green-700 dark:text-green-300',
    Inactif: 'bg-muted text-muted-foreground',
};

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Sociétés', href: '/societes' }],
    },
});
</script>

<template>
    <Head title="Sociétés" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <div class="flex items-baseline justify-between">
            <h1 class="text-xl font-semibold">Sociétés</h1>
            <span class="text-sm text-muted-foreground">
                {{ societes.total }} société{{ societes.total > 1 ? 's' : '' }}
            </span>
        </div>

        <div class="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left text-muted-foreground">
                    <tr>
                        <th class="px-4 py-2 font-medium">Numéro</th>
                        <th class="px-4 py-2 font-medium">Raison sociale</th>
                        <th class="px-4 py-2 font-medium">État</th>
                        <th class="px-4 py-2 font-medium">Ville</th>
                        <th class="px-4 py-2 font-medium">Propriétaire</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="s in societes.data" :key="s.id" class="border-t border-sidebar-border/50">
                        <td class="px-4 py-2 font-mono text-xs">{{ s.numero }}</td>
                        <td class="px-4 py-2">{{ s.raison_sociale ?? '—' }}</td>
                        <td class="px-4 py-2">
                            <span class="rounded px-1.5 py-0.5 text-xs" :class="teinteEtat[s.etat] ?? 'bg-muted'">
                                {{ s.etat }}
                            </span>
                        </td>
                        <td class="px-4 py-2">{{ s.ville ?? '—' }}</td>
                        <td class="px-4 py-2">{{ s.proprietaire ?? 'Non affectée' }}</td>
                    </tr>
                    <tr v-if="societes.data.length === 0">
                        <td colspan="5" class="px-4 py-8 text-center text-muted-foreground">
                            Aucune société dans votre périmètre.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
