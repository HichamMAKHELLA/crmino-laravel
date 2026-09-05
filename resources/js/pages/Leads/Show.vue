<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import FriseActivites from '@/components/FriseActivites.vue';
import { computed, ref } from 'vue';

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

interface ActiviteLigne {
    id: number; type: string | null; objet: string | null; resultat: string | null;
    debut_le: string; utilisateur: string | null;
    prochaine_action_le: string | null; prochaine_action_libelle: string | null;
}
interface TypeActiviteOption { id: number; libelle: string }

interface Edition {
    raison_sociale: string | null; ice: string | null; site_web: string | null; score: number | null;
    source_id: number | null; statut_id: number | null; effectif: number | null;
    ca_estime: number | null; adresse: string | null; commentaire: string | null;
}
interface RefOption { id: number; libelle: string }

const props = defineProps<{
    lead: Lead;
    palier: Palier | null;
    contacts: ContactLigne[];
    qualification: Qualification | null;
    converti: boolean;
    societeId: number | null;
    peutConvertir: boolean;
    peutModifier: boolean;
    edition: Edition;
    sources: RefOption[];
    statuts: RefOption[];
    activites: ActiviteLigne[];
    typesActivite: TypeActiviteOption[];
}>();

const page = usePage();
const erreur = computed(() => (page.props.flash as { error?: string } | undefined)?.error);

// §5 : édition. Le formulaire repart des valeurs en cours (le propriétaire est exclu).
const enEdition = ref(false);
const edit = useForm({ ...props.edition });
function enregistrerChamps() {
    edit.put(`/leads/${props.lead.id}`, { preserveScroll: true, onSuccess: () => { enEdition.value = false; } });
}

function convertir() {
    if (confirm('Convertir ce lead en société ? Les contacts basculeront vers la société.')) {
        router.post(`/leads/${props.lead.id}/convertir`);
    }
}

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
        <div v-if="erreur" class="rounded-md bg-destructive/10 px-3 py-2 text-sm text-destructive">
            {{ erreur }}
        </div>

        <!-- RG-LEA-003 : un lead converti renvoie vers sa société. -->
        <div
            v-if="converti"
            class="flex items-center justify-between rounded-md border border-primary/30 bg-primary/5 px-4 py-3 text-sm"
        >
            <span>Ce lead est converti.</span>
            <Link :href="`/societes/${societeId}`" class="font-medium text-primary hover:underline">
                Voir la société →
            </Link>
        </div>

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

        <div class="flex flex-wrap gap-2">
            <button
                v-if="!converti && peutConvertir"
                type="button"
                class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90"
                @click="convertir"
            >
                Convertir en société
            </button>
            <button
                v-if="peutModifier"
                type="button"
                class="rounded-md border border-sidebar-border/70 px-4 py-2 text-sm hover:bg-muted dark:border-sidebar-border"
                @click="enEdition = !enEdition"
            >
                {{ enEdition ? 'Fermer' : 'Modifier' }}
            </button>
        </div>

        <!-- §5 : édition (le propriétaire est EXCLU ; un lead converti ne se modifie plus). -->
        <form v-if="enEdition && peutModifier" class="grid gap-3 rounded-xl border border-sidebar-border/70 p-4 sm:grid-cols-2 dark:border-sidebar-border" @submit.prevent="enregistrerChamps">
            <label class="flex flex-col gap-1 text-sm sm:col-span-2"><span class="text-xs text-muted-foreground">Raison sociale</span><input v-model="edit.raison_sociale" type="text" class="rounded-md border bg-background px-3 py-1.5" /></label>
            <label class="flex flex-col gap-1 text-sm"><span class="text-xs text-muted-foreground">Origine</span>
                <select v-model="edit.source_id" class="rounded-md border bg-background px-3 py-1.5">
                    <option v-for="s in sources" :key="s.id" :value="s.id">{{ s.libelle }}</option>
                </select>
            </label>
            <label class="flex flex-col gap-1 text-sm"><span class="text-xs text-muted-foreground">Statut</span>
                <select v-model="edit.statut_id" class="rounded-md border bg-background px-3 py-1.5">
                    <option :value="null">—</option>
                    <option v-for="s in statuts" :key="s.id" :value="s.id">{{ s.libelle }}</option>
                </select>
            </label>
            <label class="flex flex-col gap-1 text-sm"><span class="text-xs text-muted-foreground">ICE</span><input v-model="edit.ice" type="text" class="rounded-md border bg-background px-3 py-1.5" /></label>
            <label class="flex flex-col gap-1 text-sm"><span class="text-xs text-muted-foreground">Score (0–100, vide = non scoré)</span><input v-model.number="edit.score" type="number" min="0" max="100" class="rounded-md border bg-background px-3 py-1.5" /></label>
            <label class="flex flex-col gap-1 text-sm"><span class="text-xs text-muted-foreground">Effectif</span><input v-model.number="edit.effectif" type="number" min="0" class="rounded-md border bg-background px-3 py-1.5" /></label>
            <label class="flex flex-col gap-1 text-sm"><span class="text-xs text-muted-foreground">CA estimé</span><input v-model.number="edit.ca_estime" type="number" min="0" class="rounded-md border bg-background px-3 py-1.5" /></label>
            <label class="flex flex-col gap-1 text-sm sm:col-span-2"><span class="text-xs text-muted-foreground">Site web</span><input v-model="edit.site_web" type="text" class="rounded-md border bg-background px-3 py-1.5" /></label>
            <label class="flex flex-col gap-1 text-sm sm:col-span-2"><span class="text-xs text-muted-foreground">Adresse</span><input v-model="edit.adresse" type="text" class="rounded-md border bg-background px-3 py-1.5" /></label>
            <label class="flex flex-col gap-1 text-sm sm:col-span-2"><span class="text-xs text-muted-foreground">Commentaire</span><textarea v-model="edit.commentaire" rows="2" class="rounded-md border bg-background px-3 py-1.5"></textarea></label>
            <div class="sm:col-span-2">
                <button type="submit" class="rounded-md bg-primary px-4 py-1.5 text-sm font-medium text-primary-foreground hover:opacity-90" :disabled="edit.processing">Enregistrer</button>
            </div>
        </form>

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

        <FriseActivites :cible-type="'lead'" :cible-id="lead.id" :activites="activites" :types="typesActivite" />
    </div>
</template>
