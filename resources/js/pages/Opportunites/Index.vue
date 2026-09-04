<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Carte {
    id: number;
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

defineProps<{ colonnes: Colonne[] }>();

const page = usePage();
const succes = computed(() => (page.props.flash as { success?: string } | undefined)?.success);

const mad = new Intl.NumberFormat('fr-MA', { style: 'currency', currency: 'MAD', maximumFractionDigits: 0 });
function montant(v: number): string {
    return mad.format(v);
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

        <div class="flex items-baseline justify-between">
            <h1 class="text-xl font-semibold">Pipeline</h1>
            <Link
                href="/opportunites/create"
                class="rounded-md bg-primary px-3 py-1.5 text-sm font-medium text-primary-foreground hover:opacity-90"
            >
                + Nouvelle opportunité
            </Link>
        </div>

        <!-- Board : une colonne par étape ouverte (§64). Défile horizontalement. -->
        <div class="flex flex-1 gap-4 overflow-x-auto pb-2">
            <div
                v-for="col in colonnes"
                :key="col.id"
                class="flex w-72 shrink-0 flex-col gap-3 rounded-xl border border-sidebar-border/70 bg-muted/30 p-3 dark:border-sidebar-border"
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
                    class="flex flex-col gap-1 rounded-lg border border-sidebar-border/70 bg-background p-3 text-sm hover:border-primary/50 dark:border-sidebar-border"
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
