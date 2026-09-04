<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Produit {
    id: number; code: string; designation: string;
    famille: string | null; gamme: string | null; type: string;
    prix_catalogue: number | null; actif: boolean;
}

defineProps<{ produits: Produit[]; peutGerer: boolean }>();

const page = usePage();
const succes = computed(() => (page.props.flash as { success?: string } | undefined)?.success);

const mad = new Intl.NumberFormat('fr-MA', { style: 'currency', currency: 'MAD', maximumFractionDigits: 0 });

function desactiver(id: number) {
    router.post(`/catalogue/${id}/desactiver`, {}, { preserveScroll: true });
}
function reactiver(id: number) {
    router.post(`/catalogue/${id}/reactiver`, {}, { preserveScroll: true });
}

defineOptions({ layout: { breadcrumbs: [{ title: 'Catalogue', href: '/catalogue' }] } });
</script>

<template>
    <Head title="Catalogue" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <div v-if="succes" class="rounded-md border border-green-600/30 bg-green-50 px-4 py-2 text-sm text-green-800 dark:bg-green-950/40 dark:text-green-300">
            {{ succes }}
        </div>

        <div class="flex items-baseline justify-between">
            <h1 class="text-xl font-semibold">Catalogue</h1>
            <Link v-if="peutGerer" href="/catalogue/create" class="rounded-md bg-primary px-3 py-1.5 text-sm font-medium text-primary-foreground hover:opacity-90">
                + Nouveau produit
            </Link>
        </div>

        <div class="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left text-muted-foreground">
                    <tr>
                        <th class="px-4 py-2 font-medium">Code</th>
                        <th class="px-4 py-2 font-medium">Désignation</th>
                        <th class="px-4 py-2 font-medium">Gamme</th>
                        <th class="px-4 py-2 font-medium">Type</th>
                        <th class="px-4 py-2 font-medium">Prix</th>
                        <th v-if="peutGerer" class="px-4 py-2 font-medium"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="p in produits" :key="p.id" class="border-t border-sidebar-border/50" :class="p.actif ? '' : 'opacity-60'">
                        <td class="px-4 py-2 font-mono text-xs">{{ p.code }}</td>
                        <td class="px-4 py-2">
                            {{ p.designation }}
                            <span v-if="!p.actif" class="ml-2 rounded bg-muted px-1.5 py-0.5 text-xs text-muted-foreground">Retirée</span>
                        </td>
                        <td class="px-4 py-2 text-muted-foreground">{{ p.gamme ?? '—' }}</td>
                        <td class="px-4 py-2">{{ p.type }}</td>
                        <td class="px-4 py-2 font-mono text-xs">{{ p.prix_catalogue !== null ? mad.format(p.prix_catalogue) : 'Sur devis' }}</td>
                        <td v-if="peutGerer" class="px-4 py-2 text-right">
                            <button v-if="p.actif" type="button" class="text-xs text-muted-foreground hover:text-destructive" @click="desactiver(p.id)">Retirer</button>
                            <button v-else type="button" class="text-xs text-primary hover:underline" @click="reactiver(p.id)">Remettre</button>
                        </td>
                    </tr>
                    <tr v-if="produits.length === 0">
                        <td :colspan="peutGerer ? 6 : 5" class="px-4 py-8 text-center text-muted-foreground">Catalogue vide.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
