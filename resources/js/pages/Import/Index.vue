<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface Option { id: number; libelle?: string; nom?: string }
interface ApercuLigne { ligne: number; resultat: string; raison_sociale: string | null; motif: string | null }
interface Apercu { total: number; importables: number; rejetees: number; doublons: number; apercu: ApercuLigne[] }
interface Lot {
    id: number; nom_fichier: string; statut: string;
    total: number; importees: number; rejetees: number; doublons: number; lance_le: string | null;
}

const props = defineProps<{
    sources: Option[];
    campagnes: Option[];
    lots: Lot[];
    apercu?: Apercu;
    nomFichier?: string;
}>();

const page = usePage();
const succes = computed(() => (page.props.flash as { success?: string } | undefined)?.success);

const fichier = ref<File | null>(null);
const sourceId = ref<number | null>(null);
const campagneId = ref<number | null>(null);
const enCours = ref(false);

function choisir(e: Event) {
    const f = (e.target as HTMLInputElement).files?.[0] ?? null;
    fichier.value = f;
}
function analyser() {
    if (!fichier.value) return;
    enCours.value = true;
    router.post('/import/analyser', { fichier: fichier.value }, {
        forceFormData: true,
        preserveState: true,
        onFinish: () => { enCours.value = false; },
    });
}
function importer() {
    if (!fichier.value || !sourceId.value) return;
    enCours.value = true;
    router.post('/import/executer', {
        fichier: fichier.value,
        source_id: sourceId.value,
        campagne_id: campagneId.value,
    }, {
        forceFormData: true,
        onFinish: () => { enCours.value = false; },
    });
}

const teinteResultat: Record<string, string> = {
    Importee: 'text-green-700 dark:text-green-300',
    Rejetee: 'text-destructive',
    Doublon: 'text-amber-700 dark:text-amber-400',
};
const teinteStatut: Record<string, string> = {
    Termine: 'bg-green-600/15 text-green-700 dark:text-green-300',
    Echoue: 'bg-destructive/10 text-destructive',
    EnCours: 'bg-muted text-muted-foreground',
};
function quand(iso: string | null): string {
    return iso ? new Date(iso).toLocaleString('fr-MA', { dateStyle: 'short', timeStyle: 'short' }) : '';
}

defineOptions({ layout: { breadcrumbs: [{ title: 'Import', href: '/import' }] } });
</script>

<template>
    <Head title="Import de prospects" />

    <div class="mx-auto flex w-full max-w-4xl flex-col gap-6 p-4">
        <div v-if="succes" class="rounded-md border border-green-600/30 bg-green-50 px-4 py-2 text-sm text-green-800 dark:bg-green-950/40 dark:text-green-300">
            {{ succes }}
        </div>

        <div>
            <h1 class="text-xl font-semibold">Import de prospects (§42)</h1>
            <p class="mt-1 text-sm text-muted-foreground">
                CSV ou texte. En-têtes attendues : raison_sociale, ice, telephone, gsm, email, nom_contact, prenom_contact, site_web.
            </p>
        </div>

        <!-- Étape 1 : choisir et analyser (aucune écriture) -->
        <div class="flex flex-col gap-3 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
            <input type="file" accept=".csv,.txt" class="text-sm" @change="choisir" />
            <div>
                <button
                    type="button"
                    class="rounded-md border border-sidebar-border/70 px-3 py-1.5 text-sm hover:bg-muted dark:border-sidebar-border"
                    :disabled="!fichier || enCours"
                    @click="analyser"
                >
                    Analyser
                </button>
            </div>
        </div>

        <!-- Étape 2 : aperçu + import -->
        <div v-if="apercu" class="flex flex-col gap-4 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
            <div class="flex flex-wrap gap-4 text-sm">
                <span><strong>{{ apercu.total }}</strong> lignes</span>
                <span class="text-green-700 dark:text-green-300"><strong>{{ apercu.importables }}</strong> importables</span>
                <span class="text-destructive"><strong>{{ apercu.rejetees }}</strong> rejetées</span>
                <span class="text-amber-700 dark:text-amber-400"><strong>{{ apercu.doublons }}</strong> doublons</span>
            </div>

            <div class="max-h-64 overflow-auto rounded-lg border border-sidebar-border/50">
                <table class="w-full text-sm">
                    <thead class="sticky top-0 bg-muted/50 text-left text-muted-foreground">
                        <tr>
                            <th class="px-3 py-1.5 font-medium">#</th>
                            <th class="px-3 py-1.5 font-medium">Raison sociale</th>
                            <th class="px-3 py-1.5 font-medium">Résultat</th>
                            <th class="px-3 py-1.5 font-medium">Motif</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="l in apercu.apercu" :key="l.ligne" class="border-t border-sidebar-border/40">
                            <td class="px-3 py-1">{{ l.ligne }}</td>
                            <td class="px-3 py-1">{{ l.raison_sociale ?? '—' }}</td>
                            <td class="px-3 py-1" :class="teinteResultat[l.resultat]">{{ l.resultat }}</td>
                            <td class="px-3 py-1 text-xs text-muted-foreground">{{ l.motif ?? '' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex flex-wrap items-end gap-3">
                <label class="flex flex-col gap-1 text-sm">
                    <span class="text-xs text-muted-foreground">Origine <span class="text-destructive">*</span></span>
                    <select v-model="sourceId" class="rounded-md border bg-background px-3 py-1.5">
                        <option :value="null" disabled>— Choisir —</option>
                        <option v-for="s in sources" :key="s.id" :value="s.id">{{ s.libelle }}</option>
                    </select>
                </label>
                <label class="flex flex-col gap-1 text-sm">
                    <span class="text-xs text-muted-foreground">Campagne (facultatif)</span>
                    <select v-model="campagneId" class="rounded-md border bg-background px-3 py-1.5">
                        <option :value="null">— Aucune —</option>
                        <option v-for="c in campagnes" :key="c.id" :value="c.id">{{ c.nom }}</option>
                    </select>
                </label>
                <button
                    type="button"
                    class="rounded-md bg-primary px-4 py-1.5 text-sm font-medium text-primary-foreground hover:opacity-90"
                    :disabled="!sourceId || enCours"
                    @click="importer"
                >
                    Importer {{ apercu.importables }} lead(s)
                </button>
            </div>
            <!-- §42 : la campagne omise ne se reprend pas. -->
            <p v-if="!campagneId" class="text-xs text-amber-700 dark:text-amber-400">
                Sans campagne, le rattachement ne pourra pas être repris après coup (l'import est irréversible).
            </p>
        </div>

        <!-- Historique des lots -->
        <div class="flex flex-col gap-2">
            <h2 class="text-sm font-semibold text-muted-foreground">Historique des imports</h2>
            <div class="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                <table class="w-full text-sm">
                    <thead class="bg-muted/50 text-left text-muted-foreground">
                        <tr>
                            <th class="px-3 py-2 font-medium">Fichier</th>
                            <th class="px-3 py-2 font-medium">Statut</th>
                            <th class="px-3 py-2 font-medium text-right">Total</th>
                            <th class="px-3 py-2 font-medium text-right">Importés</th>
                            <th class="px-3 py-2 font-medium text-right">Rejetés</th>
                            <th class="px-3 py-2 font-medium text-right">Doublons</th>
                            <th class="px-3 py-2 font-medium">Le</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="l in lots" :key="l.id" class="border-t border-sidebar-border/40">
                            <td class="px-3 py-2">{{ l.nom_fichier }}</td>
                            <td class="px-3 py-2"><span class="rounded px-2 py-0.5 text-xs" :class="teinteStatut[l.statut] ?? 'bg-muted'">{{ l.statut }}</span></td>
                            <td class="px-3 py-2 text-right">{{ l.total }}</td>
                            <td class="px-3 py-2 text-right">{{ l.importees }}</td>
                            <td class="px-3 py-2 text-right">{{ l.rejetees }}</td>
                            <td class="px-3 py-2 text-right">{{ l.doublons }}</td>
                            <td class="px-3 py-2 text-xs text-muted-foreground">{{ quand(l.lance_le) }}</td>
                        </tr>
                        <tr v-if="lots.length === 0">
                            <td colspan="7" class="px-3 py-6 text-center text-muted-foreground">Aucun import réalisé.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
