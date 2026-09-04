<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

interface Ligne {
    id: number;
    le: string | null;
    utilisateur: string;
    action: string;
    entite: string;
    champ: string | null;
    ancienne: string | null;
    nouvelle: string | null;
}

defineProps<{ lignes: { data: Ligne[]; total: number }; action: string | null }>();

const libelleAction: Record<string, string> = {
    Creation: 'Création', Modification: 'Modification', ChangementStatut: 'Changement de statut',
    ChangementMontant: 'Changement de montant', ChangementProprietaire: 'Changement de propriétaire',
    ChangementDroits: 'Changement de droits', SuppressionLogique: 'Retrait', Export: 'Export',
};

function quand(iso: string | null): string {
    return iso ? new Date(iso).toLocaleString('fr-MA', { dateStyle: 'short', timeStyle: 'short' }) : '—';
}

defineOptions({ layout: { breadcrumbs: [{ title: 'Journal', href: '/journal' }] } });
</script>

<template>
    <Head title="Journal d'audit" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <div class="flex items-baseline justify-between">
            <h1 class="text-xl font-semibold">Journal d'audit</h1>
            <span class="text-sm text-muted-foreground">{{ lignes.total }} entrée{{ lignes.total > 1 ? 's' : '' }}</span>
        </div>

        <p class="text-xs text-muted-foreground">
            Le journal traverse tous les portefeuilles et porte les anciennes et nouvelles valeurs de chaque champ.
        </p>

        <div class="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left text-muted-foreground">
                    <tr>
                        <th class="px-4 py-2 font-medium">Date</th>
                        <th class="px-4 py-2 font-medium">Auteur</th>
                        <th class="px-4 py-2 font-medium">Action</th>
                        <th class="px-4 py-2 font-medium">Entité</th>
                        <th class="px-4 py-2 font-medium">Changement</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="l in lignes.data" :key="l.id" class="border-t border-sidebar-border/50">
                        <td class="px-4 py-2 whitespace-nowrap text-xs text-muted-foreground">{{ quand(l.le) }}</td>
                        <td class="px-4 py-2">{{ l.utilisateur }}</td>
                        <td class="px-4 py-2">{{ libelleAction[l.action] ?? l.action }}</td>
                        <td class="px-4 py-2 font-mono text-xs">{{ l.entite }}</td>
                        <td class="px-4 py-2 text-xs">
                            <template v-if="l.champ">
                                <span class="font-medium">{{ l.champ }}</span> :
                                <span class="text-muted-foreground">{{ l.ancienne }}</span>
                                →
                                <span>{{ l.nouvelle }}</span>
                            </template>
                            <span v-else class="text-muted-foreground">—</span>
                        </td>
                    </tr>
                    <tr v-if="lignes.data.length === 0">
                        <td colspan="5" class="px-4 py-8 text-center text-muted-foreground">Aucune entrée.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
