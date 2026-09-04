<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

interface Tuiles {
    mes_leads: number;
    pipeline_pondere: number;
    nb_affaires_ouvertes: number;
    mes_taches: number;
    ca_gagne_mois: number;
    taux_gain: number | null;
}

defineProps<{ prenom: string; tuiles: Tuiles }>();

const mad = new Intl.NumberFormat('fr-MA', { style: 'currency', currency: 'MAD', maximumFractionDigits: 0 });

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Accueil', href: '/dashboard' }],
    },
});
</script>

<template>
    <Head title="Accueil" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <!-- §77 : la salutation, avant tout le reste. -->
        <h1 class="text-2xl font-semibold">Bonjour {{ prenom }}</h1>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <a href="/leads" class="flex flex-col gap-1 rounded-xl border border-sidebar-border/70 p-4 hover:border-primary/50 dark:border-sidebar-border">
                <p class="text-xs text-muted-foreground">Leads à traiter</p>
                <p class="text-3xl font-bold">{{ tuiles.mes_leads }}</p>
            </a>

            <a href="/opportunites" class="flex flex-col gap-1 rounded-xl border border-sidebar-border/70 p-4 hover:border-primary/50 dark:border-sidebar-border">
                <p class="text-xs text-muted-foreground">Pipeline pondéré</p>
                <p class="font-mono text-2xl font-bold">{{ mad.format(tuiles.pipeline_pondere) }}</p>
                <p class="text-xs text-muted-foreground">{{ tuiles.nb_affaires_ouvertes }} affaire{{ tuiles.nb_affaires_ouvertes > 1 ? 's' : '' }} ouverte{{ tuiles.nb_affaires_ouvertes > 1 ? 's' : '' }}</p>
            </a>

            <a href="/taches" class="flex flex-col gap-1 rounded-xl border border-sidebar-border/70 p-4 hover:border-primary/50 dark:border-sidebar-border">
                <p class="text-xs text-muted-foreground">Mes tâches du jour</p>
                <p class="text-3xl font-bold">{{ tuiles.mes_taches }}</p>
            </a>

            <div class="flex flex-col gap-1 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                <p class="text-xs text-muted-foreground">CA gagné ce mois</p>
                <p class="font-mono text-2xl font-bold text-green-700 dark:text-green-300">{{ mad.format(tuiles.ca_gagne_mois) }}</p>
            </div>

            <div class="flex flex-col gap-1 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                <p class="text-xs text-muted-foreground">Taux de gain</p>
                <!-- RG-IND-001 : sans affaire close, le taux n'existe pas (≠ 0 %). -->
                <p v-if="tuiles.taux_gain !== null" class="text-3xl font-bold text-primary">{{ tuiles.taux_gain }} %</p>
                <p v-else class="pt-2 text-sm text-muted-foreground">Aucune affaire close sur la période</p>
            </div>
        </div>
    </div>
</template>
