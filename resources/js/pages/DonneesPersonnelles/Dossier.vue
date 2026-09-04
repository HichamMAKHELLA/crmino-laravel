<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

interface Contact {
    id: number; nom: string; civilite: string | null; fonction: string | null;
    telephone: string | null; gsm: string | null; email: string | null;
    linkedin: string | null; rattachement: string;
}
interface ActiviteLigne { type: string | null; objet: string | null; le: string | null }

const props = defineProps<{
    produitLe: string;
    contact: Contact;
    activites: ActiviteLigne[];
    limites: string[];
}>();

function quand(iso: string | null): string {
    return iso ? new Date(iso).toLocaleString('fr-MA', { dateStyle: 'medium', timeStyle: 'short' }) : '—';
}

defineOptions({
    layout: { breadcrumbs: [{ title: 'Données personnelles', href: '/donnees-personnelles' }, { title: 'Dossier', href: '#' }] },
});
</script>

<template>
    <Head title="Dossier de données personnelles" />

    <div class="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4">
        <div>
            <h1 class="text-xl font-semibold">Dossier de données personnelles</h1>
            <!-- §72 : le dossier porte SA date — c'est une photographie. -->
            <p class="text-sm text-muted-foreground">Produit le {{ quand(produitLe) }}</p>
        </div>

        <!-- Identité -->
        <div class="grid gap-4 rounded-xl border border-sidebar-border/70 p-4 sm:grid-cols-2 dark:border-sidebar-border">
            <div><p class="text-xs text-muted-foreground">Nom</p><p class="text-sm">{{ contact.nom }}</p></div>
            <div><p class="text-xs text-muted-foreground">Fonction</p><p class="text-sm">{{ contact.fonction ?? '—' }}</p></div>
            <div><p class="text-xs text-muted-foreground">Téléphone</p><p class="text-sm">{{ contact.telephone ?? '—' }}</p></div>
            <div><p class="text-xs text-muted-foreground">GSM</p><p class="text-sm">{{ contact.gsm ?? '—' }}</p></div>
            <div><p class="text-xs text-muted-foreground">Courriel</p><p class="text-sm">{{ contact.email ?? '—' }}</p></div>
            <div><p class="text-xs text-muted-foreground">LinkedIn</p><p class="text-sm">{{ contact.linkedin ?? '—' }}</p></div>
            <div class="sm:col-span-2"><p class="text-xs text-muted-foreground">Rattachement</p><p class="text-sm">{{ contact.rattachement }}</p></div>
        </div>

        <!-- Activités -->
        <section class="flex flex-col gap-2">
            <h2 class="text-sm font-semibold text-muted-foreground">Activités enregistrées</h2>
            <ul class="flex flex-col gap-1">
                <li v-for="(a, i) in activites" :key="i" class="rounded-lg border border-sidebar-border/70 p-3 text-sm dark:border-sidebar-border">
                    <span class="font-medium">{{ a.type ?? 'Activité' }}</span><template v-if="a.objet"> — {{ a.objet }}</template>
                    <span class="ml-2 text-xs text-muted-foreground">{{ quand(a.le) }}</span>
                </li>
                <li v-if="activites.length === 0" class="text-sm text-muted-foreground">Aucune activité.</li>
            </ul>
        </section>

        <!-- §72 : les limites, nommées. -->
        <section class="rounded-xl border border-amber-500/40 bg-amber-50 p-4 text-sm dark:bg-amber-950/30">
            <p class="font-semibold text-amber-900 dark:text-amber-200">Ce que ce dossier ne couvre pas</p>
            <ul class="mt-2 list-inside list-disc text-amber-800 dark:text-amber-300">
                <li v-for="(l, i) in limites" :key="i">{{ l }}</li>
            </ul>
        </section>
    </div>
</template>
