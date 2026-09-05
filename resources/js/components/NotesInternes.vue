<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Note {
    id: number; texte: string; auteur: string | null; auteur_id: number;
    cree_le: string | null; modifie_le: string | null;
}

const props = defineProps<{
    cibleType: string;
    cibleId: number;
    moiId: number;
    notes: Note[];
}>();

const nouvelle = ref('');
const enEdition = ref<number | null>(null);
const texteEdition = ref('');

function quand(iso: string | null): string {
    return iso ? new Date(iso).toLocaleString('fr-MA', { dateStyle: 'medium', timeStyle: 'short' }) : '';
}

function ajouter() {
    if (nouvelle.value.trim() === '') return;
    router.post('/commentaires', { cible_type: props.cibleType, cible_id: props.cibleId, texte: nouvelle.value }, {
        preserveScroll: true,
        onSuccess: () => { nouvelle.value = ''; },
    });
}
function ouvrirEdition(n: Note) {
    enEdition.value = n.id;
    texteEdition.value = n.texte;
}
function enregistrer(id: number) {
    router.put(`/commentaires/${id}`, { texte: texteEdition.value }, {
        preserveScroll: true,
        onSuccess: () => { enEdition.value = null; },
    });
}
function retirer(id: number) {
    if (confirm('Retirer cette note ?')) {
        router.delete(`/commentaires/${id}`, { preserveScroll: true });
    }
}
</script>

<template>
    <section class="flex flex-col gap-3">
        <h2 class="text-sm font-semibold">Notes internes (§45)</h2>

        <!-- Saisie d'une note -->
        <form class="flex flex-col gap-2" @submit.prevent="ajouter">
            <textarea
                v-model="nouvelle"
                rows="2"
                maxlength="4000"
                placeholder="Ajouter une note interne…"
                class="rounded-md border bg-background px-3 py-2 text-sm"
            ></textarea>
            <div class="flex items-center justify-between">
                <!-- Le compteur ne paraît qu'à l'approche de la limite (§45). -->
                <span v-if="nouvelle.length > 3600" class="text-xs text-muted-foreground">{{ nouvelle.length }} / 4000</span>
                <span v-else></span>
                <button type="submit" class="rounded-md bg-primary px-3 py-1.5 text-sm font-medium text-primary-foreground hover:opacity-90">
                    Ajouter
                </button>
            </div>
        </form>

        <!-- Liste des notes -->
        <ul class="flex flex-col gap-2">
            <li v-for="n in notes" :key="n.id" class="rounded-lg border border-sidebar-border/70 p-3 text-sm dark:border-sidebar-border">
                <template v-if="enEdition === n.id">
                    <textarea v-model="texteEdition" rows="2" maxlength="4000" class="w-full rounded-md border bg-background px-3 py-2 text-sm"></textarea>
                    <div class="mt-2 flex gap-2">
                        <button type="button" class="rounded-md bg-primary px-3 py-1 text-xs font-medium text-primary-foreground hover:opacity-90" @click="enregistrer(n.id)">Enregistrer</button>
                        <button type="button" class="rounded-md border border-sidebar-border/70 px-3 py-1 text-xs hover:bg-muted dark:border-sidebar-border" @click="enEdition = null">Annuler</button>
                    </div>
                </template>
                <template v-else>
                    <!-- Rendu comme du TEXTE : Vue échappe, aucune injection (§45). -->
                    <p class="whitespace-pre-wrap">{{ n.texte }}</p>
                    <div class="mt-1 flex items-center justify-between text-xs text-muted-foreground">
                        <span>
                            {{ n.auteur ?? '—' }} · {{ quand(n.cree_le) }}
                            <span v-if="n.modifie_le"> · modifiée le {{ quand(n.modifie_le) }}</span>
                        </span>
                        <!-- Seul l'AUTEUR voit corriger / retirer. -->
                        <span v-if="n.auteur_id === moiId" class="flex gap-2">
                            <button type="button" class="hover:underline" @click="ouvrirEdition(n)">Corriger</button>
                            <button type="button" class="text-destructive hover:underline" @click="retirer(n.id)">Retirer</button>
                        </span>
                    </div>
                </template>
            </li>
            <li v-if="notes.length === 0" class="text-sm text-muted-foreground">Aucune note interne.</li>
        </ul>
    </section>
</template>
