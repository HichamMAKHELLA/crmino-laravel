<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

interface Carte {
    id: number;
    etape_id: number;
    intitule: string;
    societe: string | null;
    montant_ht: number;
    montant_pondere: number;
    probabilite: number;
}
interface Colonne {
    id: number;
    libelle: string;
    couleur: string | null;
    total_pondere: number;
    cartes: Carte[];
}

const props = defineProps<{ colonnes: Colonne[] }>();

const page = usePage();
const succes = computed(() => (page.props.flash as { success?: string } | undefined)?.success);
const erreur = computed(() => (page.props.flash as { error?: string } | undefined)?.error);

const mad = new Intl.NumberFormat('fr-MA', { style: 'currency', currency: 'MAD', maximumFractionDigits: 0 });
function montant(v: number): string {
    return mad.format(v);
}

// Copie locale réactive : le glisser-déposer met à jour OPTIMISTE, puis le
// serveur confirme. En cas d'échec, la carte revient en place (§64). Clone JSON
// (les données sont purement JSON) : robuste sur un proxy réactif, là où
// structuredClone lève.
function cloner(v: Colonne[]): Colonne[] {
    return JSON.parse(JSON.stringify(v));
}
const colonnes = ref<Colonne[]>(cloner(props.colonnes));
watch(() => props.colonnes, (v) => { colonnes.value = cloner(v); });

const enVol = ref<number | null>(null);
function debut(carte: Carte) {
    enVol.value = carte.id;
}
function deposer(cibleColonne: Colonne) {
    const id = enVol.value;
    enVol.value = null;
    if (id === null) return;

    // Retrouver la carte et sa colonne d'origine.
    const source = colonnes.value.find((c) => c.cartes.some((k) => k.id === id));
    if (!source || source.id === cibleColonne.id) return;
    const carte = source.cartes.find((k) => k.id === id)!;

    // Déplacement optimiste.
    source.cartes = source.cartes.filter((k) => k.id !== id);
    carte.etape_id = cibleColonne.id;
    cibleColonne.cartes = [...cibleColonne.cartes, carte];
    recalculer();

    router.post(`/opportunites/${id}/etape`, { etape_id: cibleColonne.id }, {
        preserveScroll: true,
        preserveState: true,
        // Sur échec (RG-OPP-005…), Inertia recharge les props ; le watch remet
        // la copie locale dans l'état serveur — la carte revient en place.
        onError: () => rendre(),
    });
}
function recalculer() {
    for (const c of colonnes.value) {
        c.total_pondere = c.cartes.reduce((s, k) => s + k.montant_pondere, 0);
    }
}
function rendre() {
    colonnes.value = cloner(props.colonnes);
}

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Pipeline', href: '/opportunites' }],
    },
});
</script>

<template>
    <Head title="Pipeline" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <div
            v-if="succes"
            class="rounded-md border border-green-600/30 bg-green-50 px-4 py-2 text-sm text-green-800 dark:bg-green-950/40 dark:text-green-300"
        >
            {{ succes }}
        </div>
        <div v-if="erreur" class="rounded-md bg-destructive/10 px-3 py-2 text-sm text-destructive">
            {{ erreur }}
        </div>

        <div class="flex items-baseline justify-between">
            <h1 class="text-xl font-semibold">Pipeline</h1>
            <Link
                href="/opportunites/create"
                class="rounded-md bg-primary px-3 py-1.5 text-sm font-medium text-primary-foreground hover:opacity-90"
            >
                + Nouvelle opportunité
            </Link>
        </div>

        <!-- Board : une colonne par étape ouverte (§64). Glisser-déposer une carte
             d'une colonne à l'autre. Défile horizontalement. -->
        <div class="flex flex-1 gap-4 overflow-x-auto pb-2">
            <div
                v-for="col in colonnes"
                :key="col.id"
                class="flex w-72 shrink-0 flex-col gap-3 rounded-xl border border-sidebar-border/70 bg-muted/30 p-3 dark:border-sidebar-border"
                @dragover.prevent
                @drop.prevent="deposer(col)"
            >
                <div class="flex items-baseline justify-between border-b border-sidebar-border/50 pb-2">
                    <span class="text-sm font-semibold" :style="{ color: col.couleur ?? undefined }">
                        {{ col.libelle }}
                    </span>
                    <span class="text-xs text-muted-foreground">{{ col.cartes.length }}</span>
                </div>
                <p class="-mt-1 text-xs text-muted-foreground">Pondéré : {{ montant(col.total_pondere) }}</p>

                <Link
                    v-for="c in col.cartes"
                    :key="c.id"
                    :href="`/opportunites/${c.id}`"
                    draggable="true"
                    class="flex cursor-grab flex-col gap-1 rounded-lg border border-sidebar-border/70 bg-background p-3 text-sm hover:border-primary/50 active:cursor-grabbing dark:border-sidebar-border"
                    :class="{ 'opacity-50': enVol === c.id }"
                    @dragstart="debut(c)"
                    @dragend="enVol = null"
                >
                    <p class="font-medium">{{ c.intitule }}</p>
                    <p class="text-xs text-muted-foreground">{{ c.societe ?? '—' }}</p>
                    <div class="mt-1 flex items-baseline justify-between">
                        <span class="font-mono text-xs">{{ montant(c.montant_ht) }}</span>
                        <span class="text-xs text-muted-foreground">{{ c.probabilite }} %</span>
                    </div>
                </Link>

                <p v-if="col.cartes.length === 0" class="py-4 text-center text-xs text-muted-foreground">
                    Aucune affaire.
                </p>
            </div>

            <p v-if="colonnes.length === 0" class="text-sm text-muted-foreground">
                Aucune étape de pipeline configurée.
            </p>
        </div>
    </div>
</template>
