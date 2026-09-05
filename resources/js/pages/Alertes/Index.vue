<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface Ligne {
    id: number; code: string; libelle: string; delai_jours: number; seuil_montant: number | null;
    actif: boolean; surveille: boolean; seuil_lu: boolean; compte: number | null; calculable: boolean;
}

const props = defineProps<{ lignes: Ligne[]; sansProducteur: string[]; peutRegler: boolean }>();

const page = usePage();
const succes = computed(() => (page.props.flash as { success?: string } | undefined)?.success);

const mad = new Intl.NumberFormat('fr-MA', { style: 'currency', currency: 'MAD', maximumFractionDigits: 0 });

// Réglage en place (une ligne à la fois).
const enEdition = ref<string | null>(null);
const brouillon = ref<{ actif: boolean; delai_jours: number; seuil_montant: number | null }>({ actif: true, delai_jours: 0, seuil_montant: null });

function ouvrir(l: Ligne) {
    enEdition.value = l.code;
    brouillon.value = { actif: l.actif, delai_jours: l.delai_jours, seuil_montant: l.seuil_montant };
}
function enregistrer(l: Ligne) {
    router.put(`/alertes/${l.id}`, brouillon.value, { preserveScroll: true, onSuccess: () => { enEdition.value = null; } });
}

defineOptions({ layout: { breadcrumbs: [{ title: 'Alertes', href: '/alertes' }] } });
</script>

<template>
    <Head title="Alertes commerciales" />

    <div class="mx-auto flex w-full max-w-4xl flex-col gap-4 p-4">
        <div v-if="succes" class="rounded-md border border-green-600/30 bg-green-50 px-4 py-2 text-sm text-green-800 dark:bg-green-950/40 dark:text-green-300">
            {{ succes }}
        </div>

        <h1 class="text-xl font-semibold">Alertes commerciales (§35)</h1>

        <!-- Un paramètre actif mais incalculable est NOMMÉ (§35). -->
        <p v-if="sansProducteur.length" class="rounded-md border border-amber-500/40 bg-amber-50 px-4 py-2 text-sm text-amber-800 dark:bg-amber-950/30 dark:text-amber-300">
            Surveillance non disponible pour : {{ sansProducteur.join(', ') }}. Ces alertes sont paramétrables mais rien ne les calcule encore.
        </p>

        <div class="flex flex-col gap-2">
            <div v-for="l in lignes" :key="l.code" class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-medium">{{ l.libelle }}</p>
                        <p class="text-xs text-muted-foreground">
                            Délai {{ l.delai_jours }} j<span v-if="l.seuil_lu && l.seuil_montant !== null"> · seuil {{ mad.format(l.seuil_montant) }}</span>
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <!-- Le compte, ou l'état de la surveillance. -->
                        <span v-if="!l.actif" class="text-xs text-muted-foreground">Désactivée — rien n'est surveillé</span>
                        <span v-else-if="!l.calculable" class="text-xs text-amber-700 dark:text-amber-400">Non calculable</span>
                        <span v-else class="text-lg font-semibold">{{ l.compte }}</span>
                        <button
                            v-if="peutRegler"
                            type="button"
                            class="rounded-md border border-sidebar-border/70 px-3 py-1 text-xs hover:bg-muted dark:border-sidebar-border"
                            @click="ouvrir(l)"
                        >
                            Régler
                        </button>
                    </div>
                </div>

                <!-- Réglage. Ni création ni suppression ; le seuil seulement si lu. -->
                <form v-if="enEdition === l.code" class="mt-3 flex flex-wrap items-end gap-3 border-t border-sidebar-border/50 pt-3" @submit.prevent="() => {}">
                    <label class="flex items-center gap-2 text-sm"><input v-model="brouillon.actif" type="checkbox" /> Active</label>
                    <label class="flex flex-col gap-1 text-sm"><span class="text-xs text-muted-foreground">Délai (jours, max 3650)</span><input v-model.number="brouillon.delai_jours" type="number" min="0" max="3650" class="w-28 rounded-md border bg-background px-2 py-1" /></label>
                    <label v-if="l.seuil_lu" class="flex flex-col gap-1 text-sm"><span class="text-xs text-muted-foreground">Seuil (MAD)</span><input v-model.number="brouillon.seuil_montant" type="number" min="0" class="w-32 rounded-md border bg-background px-2 py-1" /></label>
                    <button
                        type="button"
                        class="rounded-md bg-primary px-3 py-1.5 text-sm font-medium text-primary-foreground hover:opacity-90"
                        @click="enregistrer(l)"
                    >
                        Enregistrer
                    </button>
                </form>
            </div>
        </div>
    </div>
</template>
