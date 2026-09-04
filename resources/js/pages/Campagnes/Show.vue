<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Campagne {
    id: number; numero: string; nom: string; type: string | null; statut: string;
    date_debut: string | null; date_fin: string | null; budget: number | null;
    cible: string | null; responsable: string | null;
}
interface Roi { nb_leads: number; nb_gagnees: number; ca_gagne: number; retour: number | null }

defineProps<{ campagne: Campagne; roi: Roi }>();

const page = usePage();
const succes = computed(() => (page.props.flash as { success?: string } | undefined)?.success);

const mad = new Intl.NumberFormat('fr-MA', { style: 'currency', currency: 'MAD', maximumFractionDigits: 0 });
const teinteStatut: Record<string, string> = {
    Planifiee: 'bg-muted text-muted-foreground', EnCours: 'bg-primary/10 text-primary',
    Terminee: 'bg-green-600/15 text-green-700 dark:text-green-300', Annulee: 'bg-destructive/10 text-destructive',
};
const libelleStatut: Record<string, string> = { Planifiee: 'Planifiée', EnCours: 'En cours', Terminee: 'Terminée', Annulee: 'Annulée' };

defineOptions({
    layout: { breadcrumbs: [{ title: 'Campagnes', href: '/campagnes' }, { title: 'Fiche', href: '#' }] },
});
</script>

<template>
    <Head :title="campagne.nom" />

    <div class="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4">
        <div v-if="succes" class="rounded-md border border-green-600/30 bg-green-50 px-4 py-2 text-sm text-green-800 dark:bg-green-950/40 dark:text-green-300">
            {{ succes }}
        </div>

        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="font-mono text-xs text-muted-foreground">{{ campagne.numero }}</p>
                <h1 class="text-2xl font-semibold">{{ campagne.nom }}</h1>
                <p class="mt-1 text-sm text-muted-foreground">{{ campagne.type ?? '—' }} · {{ campagne.responsable ?? '—' }}</p>
            </div>
            <span class="rounded px-2 py-1 text-sm" :class="teinteStatut[campagne.statut] ?? 'bg-muted'">
                {{ libelleStatut[campagne.statut] ?? campagne.statut }}
            </span>
        </div>

        <!-- Retour sur investissement §12 -->
        <div class="grid gap-4 sm:grid-cols-4">
            <div class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                <p class="text-xs text-muted-foreground">Leads</p>
                <p class="text-2xl font-bold">{{ roi.nb_leads }}</p>
            </div>
            <div class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                <p class="text-xs text-muted-foreground">Affaires gagnées</p>
                <p class="text-2xl font-bold">{{ roi.nb_gagnees }}</p>
            </div>
            <div class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                <p class="text-xs text-muted-foreground">CA gagné</p>
                <p class="font-mono text-lg font-semibold">{{ mad.format(roi.ca_gagne) }}</p>
            </div>
            <div class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                <p class="text-xs text-muted-foreground">Retour</p>
                <!-- RG-IND-002 : sans budget, le retour n'existe pas (≠ zéro). -->
                <p v-if="roi.retour !== null" class="text-2xl font-bold text-primary">{{ roi.retour }}×</p>
                <p v-else class="text-sm text-muted-foreground">Non chiffré</p>
            </div>
        </div>

        <!-- Détails -->
        <div class="grid gap-4 rounded-xl border border-sidebar-border/70 p-4 sm:grid-cols-3 dark:border-sidebar-border">
            <div>
                <p class="text-xs text-muted-foreground">Budget</p>
                <p class="text-sm">{{ campagne.budget !== null ? mad.format(campagne.budget) : 'Non chiffré' }}</p>
            </div>
            <div>
                <p class="text-xs text-muted-foreground">Début</p>
                <p class="text-sm">{{ campagne.date_debut ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-muted-foreground">Fin</p>
                <p class="text-sm">{{ campagne.date_fin ?? '—' }}</p>
            </div>
            <div class="sm:col-span-3">
                <p class="text-xs text-muted-foreground">Cible</p>
                <p class="text-sm">{{ campagne.cible ?? '—' }}</p>
            </div>
        </div>
    </div>
</template>
