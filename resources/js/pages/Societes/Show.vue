<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import FriseActivites from '@/components/FriseActivites.vue';
import { computed } from 'vue';

interface Societe {
    id: number; numero: string; raison_sociale: string; etat: string;
    ice: string | null; site_web: string | null; ville: string | null;
    source: string | null; devenu_client_le: string | null; proprietaire: string | null;
}
interface ContactLigne {
    id: number; nom: string; fonction: string | null;
    gsm: string | null; telephone: string | null; email: string | null; principal: boolean;
}

interface ActiviteLigne {
    id: number; type: string | null; objet: string | null; resultat: string | null;
    debut_le: string; utilisateur: string | null;
    prochaine_action_le: string | null; prochaine_action_libelle: string | null;
}
interface TypeActiviteOption { id: number; libelle: string }

defineProps<{ societe: Societe; contacts: ContactLigne[]; activites: ActiviteLigne[]; typesActivite: TypeActiviteOption[] }>();

const page = usePage();
const succes = computed(() => (page.props.flash as { success?: string } | undefined)?.success);

const teinteEtat: Record<string, string> = {
    Prospect: 'bg-primary/10 text-primary',
    Client: 'bg-green-600/15 text-green-700 dark:text-green-300',
    Inactif: 'bg-muted text-muted-foreground',
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Sociétés', href: '/societes' },
            { title: 'Fiche', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="societe.raison_sociale" />

    <div class="mx-auto flex w-full max-w-4xl flex-col gap-6 p-4">
        <div
            v-if="succes"
            class="rounded-md border border-green-600/30 bg-green-50 px-4 py-2 text-sm text-green-800 dark:bg-green-950/40 dark:text-green-300"
        >
            {{ succes }}
        </div>

        <!-- En-tête -->
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="font-mono text-xs text-muted-foreground">{{ societe.numero }}</p>
                <h1 class="text-2xl font-semibold">{{ societe.raison_sociale }}</h1>
            </div>
            <span class="rounded px-2 py-1 text-sm" :class="teinteEtat[societe.etat] ?? 'bg-muted'">
                {{ societe.etat }}
            </span>
        </div>

        <!-- Résumé -->
        <div class="grid gap-4 rounded-xl border border-sidebar-border/70 p-4 sm:grid-cols-3 dark:border-sidebar-border">
            <div>
                <p class="text-xs text-muted-foreground">Propriétaire</p>
                <p class="text-sm">{{ societe.proprietaire || 'Non affectée' }}</p>
            </div>
            <div>
                <p class="text-xs text-muted-foreground">ICE</p>
                <p class="text-sm">{{ societe.ice ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-muted-foreground">Ville</p>
                <p class="text-sm">{{ societe.ville ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-muted-foreground">Origine</p>
                <p class="text-sm">{{ societe.source ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-muted-foreground">Site web</p>
                <p class="text-sm">{{ societe.site_web ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs text-muted-foreground">Client depuis</p>
                <p class="text-sm">{{ societe.devenu_client_le ?? '—' }}</p>
            </div>
        </div>

        <!-- Contacts (basculés depuis le lead à la conversion) -->
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

        <FriseActivites :cible-type="'societe'" :cible-id="societe.id" :activites="activites" :types="typesActivite" />
    </div>
</template>
