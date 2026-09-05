<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import FriseActivites from '@/components/FriseActivites.vue';
import NotesInternes from '@/components/NotesInternes.vue';
import Documents from '@/components/Documents.vue';
import { computed, ref } from 'vue';

interface Note {
    id: number; texte: string; auteur: string | null; auteur_id: number;
    cree_le: string | null; modifie_le: string | null;
}
interface Doc { id: number; nom: string; type: string | null; taille_octets: number; depose_le: string | null }
interface TypeDocOption { id: number; libelle: string }

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

interface Champs { ice: string | null; site_web: string | null; telephone: string | null; email: string | null; adresse: string | null; commentaire: string | null }

const props = defineProps<{
    societe: Societe; contacts: ContactLigne[]; activites: ActiviteLigne[]; typesActivite: TypeActiviteOption[];
    ciblesEtat: string[]; notes: Note[]; moiId: number;
    documents: Doc[]; typesDocument: TypeDocOption[]; peutDeposer: boolean; peutSupprimer: boolean;
    peutModifier: boolean; champs: Champs;
}>();

// §18 : édition des champs. Le formulaire repart de la valeur en cours.
const enEdition = ref(false);
const edit = useForm({
    raison_sociale: props.societe.raison_sociale,
    ice: props.champs.ice ?? '',
    site_web: props.champs.site_web ?? '',
    telephone: props.champs.telephone ?? '',
    email: props.champs.email ?? '',
    adresse: props.champs.adresse ?? '',
    commentaire: props.champs.commentaire ?? '',
});
function enregistrerChamps() {
    edit.put(`/societes/${props.societe.id}`, { preserveScroll: true, onSuccess: () => { enEdition.value = false; } });
}

const page = usePage();
const succes = computed(() => (page.props.flash as { success?: string } | undefined)?.success);
const erreur = computed(() => (page.props.flash as { error?: string } | undefined)?.error);

// §32 : changer l'état de la relation (RG-SOC-001). Une cible présélectionnée.
const nouvelEtat = ref<string>(props.ciblesEtat[0] ?? '');
function changerEtat() {
    if (!nouvelEtat.value) return;
    router.post(`/societes/${props.societe.id}/etat`, { etat: nouvelEtat.value }, { preserveScroll: true });
}

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
        <div v-if="erreur" class="rounded-md bg-destructive/10 px-3 py-2 text-sm text-destructive">
            {{ erreur }}
        </div>

        <!-- En-tête -->
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="font-mono text-xs text-muted-foreground">{{ societe.numero }}</p>
                <h1 class="text-2xl font-semibold">{{ societe.raison_sociale }}</h1>
            </div>
            <div class="flex items-center gap-2">
                <span class="rounded px-2 py-1 text-sm" :class="teinteEtat[societe.etat] ?? 'bg-muted'">
                    {{ societe.etat }}
                </span>
                <button
                    v-if="peutModifier"
                    type="button"
                    class="rounded-md border border-sidebar-border/70 px-3 py-1 text-sm hover:bg-muted dark:border-sidebar-border"
                    @click="enEdition = !enEdition"
                >
                    {{ enEdition ? 'Fermer' : 'Modifier' }}
                </button>
                <!-- §32 : l'état de la relation. La société reste visible et comptée (RG-SOC-001). -->
                <template v-if="ciblesEtat.length">
                    <select v-model="nouvelEtat" class="rounded-md border bg-background px-2 py-1 text-sm">
                        <option v-for="c in ciblesEtat" :key="c" :value="c">{{ c }}</option>
                    </select>
                    <button
                        type="button"
                        class="rounded-md border border-sidebar-border/70 px-3 py-1 text-sm hover:bg-muted dark:border-sidebar-border"
                        @click="changerEtat"
                    >
                        Changer l'état
                    </button>
                </template>
            </div>
        </div>

        <!-- §18 : formulaire d'édition (l'état, le propriétaire et Sage sont EXCLUS). -->
        <form v-if="enEdition" class="grid gap-3 rounded-xl border border-sidebar-border/70 p-4 sm:grid-cols-2 dark:border-sidebar-border" @submit.prevent="enregistrerChamps">
            <label class="flex flex-col gap-1 text-sm sm:col-span-2">
                <span class="text-xs text-muted-foreground">Raison sociale</span>
                <input v-model="edit.raison_sociale" type="text" class="rounded-md border bg-background px-3 py-1.5" />
                <span v-if="edit.errors.raison_sociale" class="text-xs text-destructive">{{ edit.errors.raison_sociale }}</span>
            </label>
            <label class="flex flex-col gap-1 text-sm"><span class="text-xs text-muted-foreground">ICE</span><input v-model="edit.ice" type="text" class="rounded-md border bg-background px-3 py-1.5" /></label>
            <label class="flex flex-col gap-1 text-sm"><span class="text-xs text-muted-foreground">Site web</span><input v-model="edit.site_web" type="text" class="rounded-md border bg-background px-3 py-1.5" /></label>
            <label class="flex flex-col gap-1 text-sm"><span class="text-xs text-muted-foreground">Téléphone</span><input v-model="edit.telephone" type="text" class="rounded-md border bg-background px-3 py-1.5" /></label>
            <label class="flex flex-col gap-1 text-sm"><span class="text-xs text-muted-foreground">Courriel</span><input v-model="edit.email" type="text" class="rounded-md border bg-background px-3 py-1.5" /></label>
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

        <Documents cible-type="Societe" :cible-id="societe.id" :documents="documents" :types-document="typesDocument" :peut-deposer="peutDeposer" :peut-supprimer="peutSupprimer" />

        <NotesInternes cible-type="Societe" :cible-id="societe.id" :moi-id="moiId" :notes="notes" />
    </div>
</template>
