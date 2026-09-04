<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Lead {
    id: number; numero: string; raison_sociale: string | null;
    ice: string | null; site_web: string | null;
    statut: string | null; source: string | null; ville: string | null;
    proprietaire: string | null; score: number | null; commentaire: string | null;
}
interface Palier { libelle: string; borne_min: number; borne_max: number; couleur: string | null }
interface ContactLigne {
    id: number; nom: string; fonction: string | null;
    telephone: string | null; gsm: string | null; email: string | null; principal: boolean;
}
interface Qualification {
    usage_sage: string; version_sage: string | null; revendeur_actuel: string | null;
    logiciel_actuel: string | null; erp_actuel: string | null;
    nb_utilisateurs: number | null; hebergement: string | null;
}

const props = defineProps<{
    lead: Lead;
    palier: Palier | null;
    contacts: ContactLigne[];
    qualification: Qualification | null;
}>();

const titre = computed(() => props.lead.raison_sociale ?? props.lead.numero);

const usageSage: Record<string, string> = {
    Oui: 'Oui', Non: 'Non', Ancien: 'Ancien utilisateur', NeSaitPas: 'Ne sait pas',
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Prospection', href: '/leads' },
            { title: 'Fiche', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="titre" />

    <div class="mx-auto flex w-full max-w-4xl flex-col gap-6 p-4">
        <!-- En-tête -->
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="font-mono text-xs text-muted-foreground">{{ lead.numero }}</p>
                <h1 class="text-2xl font-semibold">{{ titre }}</h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{ lead.statut ?? '—' }} · {{ lead.source ?? '—' }}
                </p>
            </div>

            <!-- §15 : score et palier. « Non scoré » n'est pas un zéro. -->
            <div class="rounded-xl border border-sidebar-border/70 px-4 py-3 text-right dark:border-sidebar-border">
                <template v-if="lead.score !== null && palier">
                    <p class="text-2xl font-bold" :style="{ color: palier.couleur ?? undefined }">
                        {{ lead.score }}<span class="text-sm font-normal text-muted-foreground">/100</span>
                    </p>
                    <p class="text-sm font-medium" :style="{ color: palier.couleur ?? undefined }">
                        {{ palier.libelle }}
                    </p>
                    <p class="text-xs text-muted-foreground">{{ palier.borne_min }}–{{ palier.borne_max }}</p>
                </template>
                <template v-else>
                    <p class="text-sm font-medium text-muted-foreground">Non scoré</p>
                    <p class="text-xs text-muted-foreground">à qualifier</p>
                </template>
            </div>
        </div>

        <!-- Résumé -->
        <div class="grid gap-4 rounded-xl border border-sidebar-border/70 p-4 sm:grid-cols-3 dark:border-sidebar-border">
            <div>
                <p class="text-xs text-muted-foreground">Propriétaire</p>
                <p class="text-sm">{{ lead.proprietaire || 'Non affecté' }}</p>
            </div>
            <div>
                <p class="text-xs text-muted-foreground">ICE</p>
                <p class="text-sm">{{ lead.ice ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-muted-foreground">Site web</p>
                <p class="text-sm">{{ lead.site_web ?? '—' }}</p>
            </div>
        </div>

        <!-- Contacts -->
        <section class="flex flex-col gap-2">
            <h2 class="text-sm font-semibold text-muted-foreground">Contacts</h2>
            <div class="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                <table class="w-full text-sm">
                    <tbody>
                        <tr v-for="c in contacts" :key="c.id" class="border-t border-sidebar-border/50 first:border-t-0">
                            <td class="px-4 py-2">
                                {{ c.nom }}
                                <span v-if="c.principal" class="ml-2 rounded bg-primary/10 px-1.5 py-0.5 text-xs text-primary">principal</span>
                            </td>
                            <td class="px-4 py-2 text-muted-foreground">{{ c.fonction ?? '—' }}</td>
                            <td class="px-4 py-2">{{ c.gsm ?? c.telephone ?? '—' }}</td>
                            <td class="px-4 py-2">{{ c.email ?? '—' }}</td>
                        </tr>
                        <tr v-if="contacts.length === 0">
                            <td class="px-4 py-6 text-center text-muted-foreground">Aucun contact.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Qualification §13 -->
        <section class="flex flex-col gap-2">
            <h2 class="text-sm font-semibold text-muted-foreground">Qualification (§13)</h2>
            <div v-if="qualification" class="grid gap-4 rounded-xl border border-sidebar-border/70 p-4 sm:grid-cols-2 dark:border-sidebar-border">
                <div>
                    <p class="text-xs text-muted-foreground">Usage de Sage</p>
                    <p class="text-sm">{{ usageSage[qualification.usage_sage] ?? qualification.usage_sage }}</p>
                </div>
                <div>
                    <p class="text-xs text-muted-foreground">Version Sage</p>
                    <p class="text-sm">{{ qualification.version_sage ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-muted-foreground">Revendeur actuel</p>
                    <p class="text-sm">{{ qualification.revendeur_actuel ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-muted-foreground">Logiciel / ERP actuel</p>
                    <p class="text-sm">{{ qualification.logiciel_actuel ?? qualification.erp_actuel ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-muted-foreground">Utilisateurs</p>
                    <p class="text-sm">{{ qualification.nb_utilisateurs ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs text-muted-foreground">Hébergement</p>
                    <p class="text-sm">{{ qualification.hebergement ?? '—' }}</p>
                </div>
            </div>
            <p v-else class="rounded-xl border border-dashed border-sidebar-border/70 p-4 text-sm text-muted-foreground dark:border-sidebar-border">
                Pas encore qualifié.
            </p>
        </section>
    </div>
</template>
