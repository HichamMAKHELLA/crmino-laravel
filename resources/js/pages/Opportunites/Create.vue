<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

interface SocieteOption { id: number; raison_sociale: string }
interface EtapeOption { id: number; libelle: string; probabilite: number }

const props = defineProps<{
    societes: SocieteOption[];
    etapes: EtapeOption[];
    societePreselectionnee: number | null;
}>();

const form = useForm({
    intitule: '',
    societe_id: props.societePreselectionnee as number | null,
    etape_id: props.etapes[0]?.id ?? (null as number | null),
    montant_ht: null as number | null,
    probabilite: null as number | null,
    date_cloture_estimee: '',
    commentaire: '',
});

const etapeChoisie = computed(() => props.etapes.find((e) => e.id === form.etape_id));
// §75 : le pondéré se voit à la saisie. À défaut de probabilité saisie, celle de l'étape.
const probaEffective = computed(() => form.probabilite ?? etapeChoisie.value?.probabilite ?? 0);
const pondere = computed(() => Math.round(((form.montant_ht ?? 0) * probaEffective.value) / 100));
const mad = new Intl.NumberFormat('fr-MA', { style: 'currency', currency: 'MAD', maximumFractionDigits: 0 });

function soumettre() {
    form.post('/opportunites');
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Pipeline', href: '/opportunites' },
            { title: 'Nouvelle opportunité', href: '/opportunites/create' },
        ],
    },
});
</script>

<template>
    <Head title="Nouvelle opportunité" />

    <form class="mx-auto flex w-full max-w-2xl flex-col gap-6 p-4" @submit.prevent="soumettre">
        <h1 class="text-xl font-semibold">Nouvelle opportunité</h1>

        <div class="flex flex-col gap-4 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
            <label class="flex flex-col gap-1 text-sm">
                <span>Intitulé <span class="text-destructive">*</span></span>
                <input v-model="form.intitule" type="text" class="rounded-md border bg-background px-3 py-1.5" />
                <span v-if="form.errors.intitule" class="text-destructive">{{ form.errors.intitule }}</span>
            </label>

            <label class="flex flex-col gap-1 text-sm">
                <span>Société <span class="text-destructive">*</span></span>
                <select v-model="form.societe_id" class="rounded-md border bg-background px-3 py-1.5">
                    <option :value="null" disabled>— Choisir —</option>
                    <option v-for="s in societes" :key="s.id" :value="s.id">{{ s.raison_sociale }}</option>
                </select>
                <span v-if="form.errors.societe_id" class="text-destructive">{{ form.errors.societe_id }}</span>
            </label>

            <div class="grid gap-4 sm:grid-cols-2">
                <label class="flex flex-col gap-1 text-sm">
                    <span>Étape <span class="text-destructive">*</span></span>
                    <select v-model="form.etape_id" class="rounded-md border bg-background px-3 py-1.5">
                        <option v-for="e in etapes" :key="e.id" :value="e.id">{{ e.libelle }} ({{ e.probabilite }} %)</option>
                    </select>
                </label>
                <label class="flex flex-col gap-1 text-sm">
                    <span>Probabilité (%)</span>
                    <input
                        v-model.number="form.probabilite"
                        type="number" min="0" max="100"
                        :placeholder="String(etapeChoisie?.probabilite ?? 0)"
                        class="rounded-md border bg-background px-3 py-1.5"
                    />
                </label>
                <label class="flex flex-col gap-1 text-sm">
                    <span>Montant HT (MAD)</span>
                    <input v-model.number="form.montant_ht" type="number" min="0" class="rounded-md border bg-background px-3 py-1.5" />
                </label>
                <label class="flex flex-col gap-1 text-sm">
                    <span>Clôture estimée</span>
                    <input v-model="form.date_cloture_estimee" type="date" class="rounded-md border bg-background px-3 py-1.5" />
                </label>
            </div>

            <p class="rounded-md bg-muted/50 px-3 py-2 text-sm">
                Pondéré : <strong>{{ mad.format(pondere) }}</strong>
                <span class="text-muted-foreground">({{ probaEffective }} % de {{ mad.format(form.montant_ht ?? 0) }})</span>
            </p>
        </div>

        <div class="flex items-center gap-3">
            <button
                type="submit"
                class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90"
                :disabled="form.processing"
            >
                Ouvrir l'affaire
            </button>
            <a href="/opportunites" class="text-sm text-muted-foreground hover:underline">Annuler</a>
        </div>
    </form>
</template>
