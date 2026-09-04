<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Opportunite {
    id: number; numero: string; intitule: string; statut: string;
    etape: string | null; probabilite: number; montant_ht: number; montant_pondere: number;
    motif_perte: string | null; commentaire_perte: string | null; proprietaire: string | null;
}
interface SocieteLien { id: number | null; numero: string | null; raison_sociale: string | null; etat: string | null }
interface Motif { id: number; libelle: string; commentaire_obligatoire: boolean }

const props = defineProps<{
    opportunite: Opportunite;
    societe: SocieteLien;
    peutCloturer: boolean;
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

const perte = useForm({ motif_perte_id: null as number | null, commentaire: '' });
const motifChoisi = computed(() => props.motifs.find((m) => m.id === perte.motif_perte_id));
function perdre() {
    perte.post(`/opportunites/${props.opportunite.id}/perdre`);
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
