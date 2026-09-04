<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

interface Ligne {
    id: number; numero: string; nom: string; type: string | null;
    statut: string; budget: number | null; nb_leads: number; responsable: string | null;
}

defineProps<{ campagnes: { data: Ligne[]; total: number } }>();

const teinteStatut: Record<string, string> = {
    Planifiee: 'bg-muted text-muted-foreground',
    EnCours: 'bg-primary/10 text-primary',
    Terminee: 'bg-green-600/15 text-green-700 dark:text-green-300',
    Annulee: 'bg-destructive/10 text-destructive',
};
const libelleStatut: Record<string, string> = { Planifiee: 'Planifiée', EnCours: 'En cours', Terminee: 'Terminée', Annulee: 'Annulée' };
const mad = new Intl.NumberFormat('fr-MA', { style: 'currency', currency: 'MAD', maximumFractionDigits: 0 });

defineOptions({ layout: { breadcrumbs: [{ title: 'Campagnes', href: '/campagnes' }] } });
</script>

<template>
    <Head title="Campagnes" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <div class="flex items-baseline justify-between">
            <h1 class="text-xl font-semibold">Campagnes</h1>
            <div class="flex items-center gap-3">
                <span class="text-sm text-muted-foreground">{{ campagnes.total }} campagne{{ campagnes.total > 1 ? 's' : '' }}</span>
                <Link href="/campagnes/create" class="rounded-md bg-primary px-3 py-1.5 text-sm font-medium text-primary-foreground hover:opacity-90">
                    + Nouvelle campagne
                </Link>
            </div>
        </div>

        <div class="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left text-muted-foreground">
                    <tr>
                        <th class="px-4 py-2 font-medium">Numéro</th>
                        <th class="px-4 py-2 font-medium">Nom</th>
                        <th class="px-4 py-2 font-medium">Type</th>
                        <th class="px-4 py-2 font-medium">Statut</th>
                        <th class="px-4 py-2 font-medium">Budget</th>
                        <th class="px-4 py-2 font-medium">Leads</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="c in campagnes.data" :key="c.id" class="border-t border-sidebar-border/50">
                        <td class="px-4 py-2 font-mono text-xs">
                            <Link :href="`/campagnes/${c.id}`" class="text-primary hover:underline">{{ c.numero }}</Link>
                        </td>
                        <td class="px-4 py-2">{{ c.nom }}</td>
                        <td class="px-4 py-2 text-muted-foreground">{{ c.type ?? '—' }}</td>
                        <td class="px-4 py-2">
                            <span class="rounded px-1.5 py-0.5 text-xs" :class="teinteStatut[c.statut] ?? 'bg-muted'">
                                {{ libelleStatut[c.statut] ?? c.statut }}
                            </span>
                        </td>
                        <td class="px-4 py-2 font-mono text-xs">{{ c.budget !== null ? mad.format(c.budget) : 'Non chiffré' }}</td>
                        <td class="px-4 py-2">{{ c.nb_leads }}</td>
                    </tr>
                    <tr v-if="campagnes.data.length === 0">
                        <td colspan="6" class="px-4 py-8 text-center text-muted-foreground">Aucune campagne.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
