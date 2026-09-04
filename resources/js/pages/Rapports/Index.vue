<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

interface LignePrev {
    etape: string; nb: number; montant: number; pondere: number; probabilite_effective: number | null;
}
interface LigneMotif { motif: string; nb: number; montant: number }

const props = defineProps<{
    onglet: string;
    du: string | null;
    au: string | null;
    previsionnel: LignePrev[] | null;
    motifs: LigneMotif[] | null;
}>();

const mad = new Intl.NumberFormat('fr-MA', { style: 'currency', currency: 'MAD', maximumFractionDigits: 0 });

const du = ref(props.du ?? '');
const au = ref(props.au ?? '');
function filtrer() {
    router.get('/rapports', { onglet: 'motifs', du: du.value || undefined, au: au.value || undefined }, { preserveState: true });
}

function totalPrev(champ: 'montant' | 'pondere'): number {
    return (props.previsionnel ?? []).reduce((s, l) => s + l[champ], 0);
}

defineOptions({ layout: { breadcrumbs: [{ title: 'Rapports', href: '/rapports' }] } });
</script>

<template>
    <Head title="Rapports" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <h1 class="text-xl font-semibold">Rapports</h1>

        <!-- Onglets (dans l'adresse, §77) -->
        <div class="flex gap-1 border-b border-sidebar-border/70 dark:border-sidebar-border">
            <Link
                href="/rapports?onglet=previsionnel"
                class="rounded-t-md px-4 py-2 text-sm font-medium"
                :class="onglet === 'previsionnel' ? 'border-b-2 border-primary text-primary' : 'text-muted-foreground hover:text-foreground'"
            >
                Prévisionnel (§75)
            </Link>
            <Link
                href="/rapports?onglet=motifs"
                class="rounded-t-md px-4 py-2 text-sm font-medium"
                :class="onglet === 'motifs' ? 'border-b-2 border-primary text-primary' : 'text-muted-foreground hover:text-foreground'"
            >
                Motifs de perte (§38)
            </Link>
        </div>

        <!-- §75 : prévisionnel par étape -->
        <div v-if="onglet === 'previsionnel'" class="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left text-muted-foreground">
                    <tr>
                        <th class="px-4 py-2 font-medium">Étape</th>
                        <th class="px-4 py-2 font-medium text-right">Affaires</th>
                        <th class="px-4 py-2 font-medium text-right">Montant</th>
                        <th class="px-4 py-2 font-medium text-right">Pondéré</th>
                        <th class="px-4 py-2 font-medium text-right">Prob. effective</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="l in previsionnel" :key="l.etape" class="border-t border-sidebar-border/50">
                        <td class="px-4 py-2">{{ l.etape }}</td>
                        <td class="px-4 py-2 text-right">{{ l.nb }}</td>
                        <td class="px-4 py-2 text-right font-mono text-xs">{{ mad.format(l.montant) }}</td>
                        <td class="px-4 py-2 text-right font-mono text-xs">{{ mad.format(l.pondere) }}</td>
                        <td class="px-4 py-2 text-right">{{ l.probabilite_effective !== null ? l.probabilite_effective + ' %' : '—' }}</td>
                    </tr>
                    <tr v-if="(previsionnel ?? []).length === 0">
                        <td colspan="5" class="px-4 py-8 text-center text-muted-foreground">Aucune affaire ouverte.</td>
                    </tr>
                </tbody>
                <tfoot v-if="(previsionnel ?? []).length" class="border-t-2 border-sidebar-border/70 font-semibold">
                    <tr>
                        <td class="px-4 py-2">Total</td>
                        <td></td>
                        <td class="px-4 py-2 text-right font-mono text-xs">{{ mad.format(totalPrev('montant')) }}</td>
                        <td class="px-4 py-2 text-right font-mono text-xs">{{ mad.format(totalPrev('pondere')) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- §38 : motifs de perte -->
        <div v-else class="flex flex-col gap-3">
            <div class="flex flex-wrap items-end gap-3">
                <label class="flex flex-col gap-1 text-sm">
                    <span class="text-xs text-muted-foreground">Du</span>
                    <input v-model="du" type="date" class="rounded-md border bg-background px-3 py-1.5" />
                </label>
                <label class="flex flex-col gap-1 text-sm">
                    <span class="text-xs text-muted-foreground">Au (exclu)</span>
                    <input v-model="au" type="date" class="rounded-md border bg-background px-3 py-1.5" />
                </label>
                <button type="button" class="rounded-md bg-primary px-3 py-1.5 text-sm font-medium text-primary-foreground hover:opacity-90" @click="filtrer">
                    Filtrer
                </button>
            </div>

            <div class="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                <table class="w-full text-sm">
                    <thead class="bg-muted/50 text-left text-muted-foreground">
                        <tr>
                            <th class="px-4 py-2 font-medium">Motif</th>
                            <th class="px-4 py-2 font-medium text-right">Affaires perdues</th>
                            <th class="px-4 py-2 font-medium text-right">Montant perdu</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="l in motifs" :key="l.motif" class="border-t border-sidebar-border/50">
                            <td class="px-4 py-2">{{ l.motif }}</td>
                            <td class="px-4 py-2 text-right">{{ l.nb }}</td>
                            <td class="px-4 py-2 text-right font-mono text-xs">{{ mad.format(l.montant) }}</td>
                        </tr>
                        <tr v-if="(motifs ?? []).length === 0">
                            <td colspan="3" class="px-4 py-8 text-center text-muted-foreground">Aucune perte sur la période.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
