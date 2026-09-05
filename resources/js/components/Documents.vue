<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Doc { id: number; nom: string; type: string | null; taille_octets: number; depose_le: string | null }
interface TypeOption { id: number; libelle: string }

const props = defineProps<{
    cibleType: string;
    cibleId: number;
    documents: Doc[];
    typesDocument: TypeOption[];
    peutDeposer: boolean;
    peutSupprimer: boolean;
}>();

const fichier = ref<File | null>(null);
const typeId = ref<number | null>(null);
const enCours = ref(false);

function choisir(e: Event) {
    fichier.value = (e.target as HTMLInputElement).files?.[0] ?? null;
}
function deposer() {
    if (!fichier.value) return;
    enCours.value = true;
    router.post('/documents', {
        cible_type: props.cibleType, cible_id: props.cibleId,
        type_document_id: typeId.value, fichier: fichier.value,
    }, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => { fichier.value = null; typeId.value = null; },
        onFinish: () => { enCours.value = false; },
    });
}
function retirer(id: number) {
    if (confirm('Retirer ce document ? Il reste conservé (retrait logique, §47).')) {
        router.delete(`/documents/${id}`, { preserveScroll: true });
    }
}

function taille(o: number): string {
    if (o < 1024) return `${o} o`;
    if (o < 1024 * 1024) return `${Math.round(o / 1024)} Ko`;
    return `${(o / (1024 * 1024)).toFixed(1)} Mo`;
}
function quand(iso: string | null): string {
    return iso ? new Date(iso).toLocaleDateString('fr-MA', { dateStyle: 'medium' }) : '';
}
</script>

<template>
    <section class="flex flex-col gap-3">
        <h2 class="text-sm font-semibold">Documents (§44)</h2>

        <!-- Dépôt -->
        <div v-if="peutDeposer" class="flex flex-wrap items-end gap-3 rounded-lg border border-sidebar-border/70 p-3 dark:border-sidebar-border">
            <input type="file" class="text-sm" @change="choisir" />
            <select v-model="typeId" class="rounded-md border bg-background px-2 py-1 text-sm">
                <option :value="null">— Type —</option>
                <option v-for="t in typesDocument" :key="t.id" :value="t.id">{{ t.libelle }}</option>
            </select>
            <button
                type="button"
                class="rounded-md bg-primary px-3 py-1.5 text-sm font-medium text-primary-foreground hover:opacity-90"
                :disabled="!fichier || enCours"
                @click="deposer"
            >
                Déposer
            </button>
        </div>

        <!-- Liste -->
        <ul class="flex flex-col gap-1">
            <li v-for="d in documents" :key="d.id" class="flex items-center justify-between gap-3 rounded-lg border border-sidebar-border/70 p-3 text-sm dark:border-sidebar-border">
                <div class="min-w-0">
                    <a :href="`/documents/${d.id}/telecharger`" class="font-medium text-primary hover:underline">{{ d.nom }}</a>
                    <p class="text-xs text-muted-foreground">
                        {{ d.type ?? 'Sans type' }} · {{ taille(d.taille_octets) }} · {{ quand(d.depose_le) }}
                    </p>
                </div>
                <button v-if="peutSupprimer" type="button" class="shrink-0 text-xs text-destructive hover:underline" @click="retirer(d.id)">
                    Retirer
                </button>
            </li>
            <li v-if="documents.length === 0" class="text-sm text-muted-foreground">Aucun document.</li>
        </ul>
    </section>
</template>
