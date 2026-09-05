<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface Opportunite {
    id: number; numero: string; intitule: string; statut: string;
    etape: string | null; probabilite: number; montant_ht: number; montant_pondere: number;
    motif_perte: string | null; commentaire_perte: string | null; proprietaire: string | null;
}
interface SocieteLien { id: number | null; numero: string | null; raison_sociale: string | null; etat: string | null }
interface Motif { id: number; libelle: string; commentaire_obligatoire: boolean }
interface Ligne { id?: number; produit_id: number | null; designation: string; quantite: number; unite: string | null; prix_unitaire: number; montant_ht?: number }
interface ProduitCat { id: number; designation: string; prix_catalogue: number | null; unite: string | null }

const props = defineProps<{
    opportunite: Opportunite;
    societe: SocieteLien;
    peutCloturer: boolean;
    peutModifier: boolean;
    lignes: (Ligne & { produit: string | null })[];
    catalogue: ProduitCat[];
    motifs: Motif[];
}>();

const page = usePage();
const succes = computed(() => (page.props.flash as { success?: string } | undefined)?.success);
const erreur = computed(() => (page.props.flash as { error?: string } | undefined)?.error);

const ouverte = computed(() => props.opportunite.statut === 'Ouverte');
const mad = new Intl.NumberFormat('fr-MA', { style: 'currency', currency: 'MAD', maximumFractionDigits: 0 });

const teinteStatut: Record<string, string> = {
    Ouverte: 'bg-primary/10 text-primary',
    Gagnee: 'bg-green-600/15 text-green-700 dark:text-green-300',
    Perdue: 'bg-destructive/10 text-destructive',
};
const libelleStatut: Record<string, string> = { Ouverte: 'Ouverte', Gagnee: 'Gagnée', Perdue: 'Perdue' };

function gagner() {
    if (confirm('Marquer cette affaire GAGNÉE ? La société deviendra cliente.')) {
        router.post(`/opportunites/${props.opportunite.id}/gagner`);
    }
}

// §25 : édition des champs. Le montant est verrouillé sur une affaire close (RG-OPP-007).
const enEdition = ref(false);
const edit = useForm({
    intitule: props.opportunite.intitule,
    montant_ht: props.opportunite.montant_ht,
    probabilite: props.opportunite.probabilite,
    commentaire: '',
});
function enregistrerChamps() {
    edit.put(`/opportunites/${props.opportunite.id}`, { preserveScroll: true, onSuccess: () => { enEdition.value = false; } });
}

const perte = useForm({ motif_perte_id: null as number | null, commentaire: '' });
const motifChoisi = computed(() => props.motifs.find((m) => m.id === perte.motif_perte_id));
function perdre() {
    perte.post(`/opportunites/${props.opportunite.id}/perdre`);
}

// §28 : le détail par produit. Une copie locale, éditable, remplacée en bloc.
const lignes = ref<Ligne[]>(props.lignes.map((l) => ({
    produit_id: l.produit_id ?? null, designation: l.designation,
    quantite: l.quantite, unite: l.unite ?? null, prix_unitaire: l.prix_unitaire,
})));
const enregistreLignes = ref(false);

function ajouterLigne() {
    lignes.value.push({ produit_id: null, designation: '', quantite: 1, unite: null, prix_unitaire: 0 });
}
function retirerLigne(i: number) {
    lignes.value.splice(i, 1);
}
// Le catalogue est une AIDE (§28) : choisir un produit préremplit, sans fermer la saisie libre.
function choisirProduit(l: Ligne) {
    const p = props.catalogue.find((c) => c.id === l.produit_id);
    if (p) {
        if (!l.designation) l.designation = p.designation;
        if (l.prix_unitaire === 0 && p.prix_catalogue !== null) l.prix_unitaire = p.prix_catalogue;
        if (!l.unite && p.unite) l.unite = p.unite;
    }
}
const totalLignes = computed(() => lignes.value.reduce((s, l) => s + Number(l.quantite) * Number(l.prix_unitaire), 0));

function enregistrer() {
    enregistreLignes.value = true;
    router.post(`/opportunites/${props.opportunite.id}/lignes`, { lignes: lignes.value }, {
        preserveScroll: true,
        onFinish: () => { enregistreLignes.value = false; },
    });
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Pipeline', href: '/opportunites' },
            { title: 'Affaire', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="opportunite.intitule" />

    <div class="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4">
        <div v-if="succes" class="rounded-md border border-green-600/30 bg-green-50 px-4 py-2 text-sm text-green-800 dark:bg-green-950/40 dark:text-green-300">
            {{ succes }}
        </div>
        <div v-if="erreur" class="rounded-md bg-destructive/10 px-3 py-2 text-sm text-destructive">
            {{ erreur }}
        </div>

        <!-- En-tête -->
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="font-mono text-xs text-muted-foreground">{{ opportunite.numero }}</p>
                <h1 class="text-2xl font-semibold">{{ opportunite.intitule }}</h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    <Link :href="`/societes/${societe.id}`" class="text-primary hover:underline">
                        {{ societe.raison_sociale }}
                    </Link>
                    · {{ opportunite.etape ?? '—' }}
                </p>
            </div>
            <span class="rounded px-2 py-1 text-sm" :class="teinteStatut[opportunite.statut] ?? 'bg-muted'">
                {{ libelleStatut[opportunite.statut] ?? opportunite.statut }}
            </span>
        </div>

        <div v-if="peutModifier && ouverte">
            <button type="button" class="rounded-md border border-sidebar-border/70 px-3 py-1 text-sm hover:bg-muted dark:border-sidebar-border" @click="enEdition = !enEdition">
                {{ enEdition ? 'Fermer' : 'Modifier' }}
            </button>
        </div>
        <!-- §25 : édition (propriétaire, étape, statut, société EXCLUS). -->
        <form v-if="enEdition && ouverte" class="grid gap-3 rounded-xl border border-sidebar-border/70 p-4 sm:grid-cols-2 dark:border-sidebar-border" @submit.prevent="enregistrerChamps">
            <label class="flex flex-col gap-1 text-sm sm:col-span-2"><span class="text-xs text-muted-foreground">Intitulé</span><input v-model="edit.intitule" type="text" class="rounded-md border bg-background px-3 py-1.5" /></label>
            <label class="flex flex-col gap-1 text-sm"><span class="text-xs text-muted-foreground">Montant HT</span><input v-model.number="edit.montant_ht" type="number" min="0" step="0.01" class="rounded-md border bg-background px-3 py-1.5" /></label>
            <label class="flex flex-col gap-1 text-sm"><span class="text-xs text-muted-foreground">Probabilité (%)</span><input v-model.number="edit.probabilite" type="number" min="0" max="100" class="rounded-md border bg-background px-3 py-1.5" /></label>
            <label class="flex flex-col gap-1 text-sm sm:col-span-2"><span class="text-xs text-muted-foreground">Commentaire</span><textarea v-model="edit.commentaire" rows="2" class="rounded-md border bg-background px-3 py-1.5"></textarea></label>
            <div class="sm:col-span-2">
                <button type="submit" class="rounded-md bg-primary px-4 py-1.5 text-sm font-medium text-primary-foreground hover:opacity-90" :disabled="edit.processing">Enregistrer</button>
            </div>
        </form>

        <!-- Chiffres -->
        <div class="grid gap-4 rounded-xl border border-sidebar-border/70 p-4 sm:grid-cols-3 dark:border-sidebar-border">
            <div>
                <p class="text-xs text-muted-foreground">Montant HT</p>
                <p class="font-mono text-sm">{{ mad.format(opportunite.montant_ht) }}</p>
            </div>
            <div>
                <p class="text-xs text-muted-foreground">Pondéré ({{ opportunite.probabilite }} %)</p>
                <p class="font-mono text-sm">{{ mad.format(opportunite.montant_pondere) }}</p>
            </div>
            <div>
                <p class="text-xs text-muted-foreground">Propriétaire</p>
                <p class="text-sm">{{ opportunite.proprietaire ?? '—' }}</p>
            </div>
        </div>

        <!-- Détail par produit (§28). Éditable seulement sur une affaire ouverte (RG-OPP-006). -->
        <section class="flex flex-col gap-3">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold">Détail par produit (§28)</h2>
                <span class="font-mono text-sm">{{ mad.format(totalLignes) }}</span>
            </div>

            <!-- Affaire ouverte : saisie -->
            <div v-if="ouverte && peutModifier" class="flex flex-col gap-2">
                <div class="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <table class="w-full text-sm">
                        <thead class="bg-muted/50 text-left text-muted-foreground">
                            <tr>
                                <th class="px-2 py-2 font-medium">Produit</th>
                                <th class="px-2 py-2 font-medium">Désignation</th>
                                <th class="px-2 py-2 font-medium text-right">Qté</th>
                                <th class="px-2 py-2 font-medium text-right">Prix unit.</th>
                                <th class="px-2 py-2 font-medium text-right">Montant</th>
                                <th class="px-2 py-2"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(l, i) in lignes" :key="i" class="border-t border-sidebar-border/50">
                                <td class="px-2 py-1">
                                    <select v-model="l.produit_id" class="w-40 rounded-md border bg-background px-2 py-1" @change="choisirProduit(l)">
                                        <option :value="null">— Libre —</option>
                                        <option v-for="p in catalogue" :key="p.id" :value="p.id">{{ p.designation }}</option>
                                    </select>
                                </td>
                                <td class="px-2 py-1">
                                    <input v-model="l.designation" type="text" class="w-full rounded-md border bg-background px-2 py-1" />
                                </td>
                                <td class="px-2 py-1">
                                    <input v-model.number="l.quantite" type="number" min="0.01" step="0.01" class="w-20 rounded-md border bg-background px-2 py-1 text-right" />
                                </td>
                                <td class="px-2 py-1">
                                    <input v-model.number="l.prix_unitaire" type="number" min="0" step="0.01" class="w-28 rounded-md border bg-background px-2 py-1 text-right" />
                                </td>
                                <td class="px-2 py-1 text-right font-mono text-xs">{{ mad.format(Number(l.quantite) * Number(l.prix_unitaire)) }}</td>
                                <td class="px-2 py-1 text-right">
                                    <button type="button" class="text-destructive hover:underline" @click="retirerLigne(i)">Retirer</button>
                                </td>
                            </tr>
                            <tr v-if="lignes.length === 0">
                                <td colspan="6" class="px-2 py-4 text-center text-muted-foreground">Aucune ligne. Ajoutez-en une.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="flex items-center gap-3">
                    <button type="button" class="rounded-md border border-sidebar-border/70 px-3 py-1.5 text-sm hover:bg-muted dark:border-sidebar-border" @click="ajouterLigne">
                        Ajouter une ligne
                    </button>
                    <button type="button" class="rounded-md bg-primary px-4 py-1.5 text-sm font-medium text-primary-foreground hover:opacity-90" :disabled="enregistreLignes" @click="enregistrer">
                        Enregistrer le détail
                    </button>
                </div>
            </div>

            <!-- Affaire close ou lecture seule -->
            <div v-else class="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                <table class="w-full text-sm">
                    <thead class="bg-muted/50 text-left text-muted-foreground">
                        <tr>
                            <th class="px-4 py-2 font-medium">Désignation</th>
                            <th class="px-4 py-2 font-medium text-right">Qté</th>
                            <th class="px-4 py-2 font-medium text-right">Prix unit.</th>
                            <th class="px-4 py-2 font-medium text-right">Montant</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(l, i) in props.lignes" :key="i" class="border-t border-sidebar-border/50">
                            <td class="px-4 py-2">{{ l.designation }}<span v-if="l.produit" class="ml-1 text-xs text-muted-foreground">({{ l.produit }})</span></td>
                            <td class="px-4 py-2 text-right">{{ l.quantite }}</td>
                            <td class="px-4 py-2 text-right font-mono text-xs">{{ mad.format(l.prix_unitaire) }}</td>
                            <td class="px-4 py-2 text-right font-mono text-xs">{{ mad.format(l.montant_ht ?? 0) }}</td>
                        </tr>
                        <tr v-if="props.lignes.length === 0">
                            <td colspan="4" class="px-4 py-4 text-center text-muted-foreground">Aucune ligne.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p v-if="!ouverte" class="text-xs text-muted-foreground">Affaire close : le détail ne se modifie plus (RG-OPP-006).</p>
        </section>

        <!-- Clôture (RG-OPP-002/003/005) -->
        <section v-if="ouverte && peutCloturer" class="flex flex-col gap-4">
            <div class="flex items-center gap-3">
                <button
                    type="button"
                    class="rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white hover:opacity-90"
                    @click="gagner"
                >
                    Marquer gagnée
                </button>
            </div>

            <form class="flex flex-col gap-3 rounded-xl border border-destructive/30 p-4" @submit.prevent="perdre">
                <p class="text-sm font-medium">Clôturer en perte</p>
                <label class="flex flex-col gap-1 text-sm">
                    <span>Motif <span class="text-destructive">*</span></span>
                    <select v-model="perte.motif_perte_id" class="rounded-md border bg-background px-3 py-1.5">
                        <option :value="null" disabled>— Choisir —</option>
                        <option v-for="m in motifs" :key="m.id" :value="m.id">{{ m.libelle }}</option>
                    </select>
                    <span v-if="perte.errors.motif_perte_id" class="text-destructive">{{ perte.errors.motif_perte_id }}</span>
                </label>
                <label class="flex flex-col gap-1 text-sm">
                    <span>
                        Commentaire
                        <span v-if="motifChoisi?.commentaire_obligatoire" class="text-destructive">*</span>
                    </span>
                    <textarea v-model="perte.commentaire" rows="2" class="rounded-md border bg-background px-3 py-1.5"></textarea>
                    <span v-if="perte.errors.commentaire" class="text-destructive">{{ perte.errors.commentaire }}</span>
                </label>
                <div>
                    <button type="submit" class="rounded-md border border-destructive/50 px-4 py-2 text-sm font-medium text-destructive hover:bg-destructive/10" :disabled="perte.processing">
                        Clôturer en perte
                    </button>
                </div>
            </form>
        </section>

        <!-- Affaire close : RG-OPP-005 -->
        <div v-else-if="!ouverte" class="rounded-md border border-sidebar-border/70 bg-muted/30 p-4 text-sm dark:border-sidebar-border">
            <p class="font-medium">Cette affaire est close. Son étape ne change plus (RG-OPP-005).</p>
            <p v-if="opportunite.motif_perte" class="mt-1 text-muted-foreground">
                Motif de perte : {{ opportunite.motif_perte }}<template v-if="opportunite.commentaire_perte"> — {{ opportunite.commentaire_perte }}</template>
            </p>
        </div>
    </div>
</template>
