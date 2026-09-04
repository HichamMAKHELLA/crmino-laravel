<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Tache {
    id: number;
    titre: string;
    type: string | null;
    priorite: string | null;
    echeance_le: string | null;
    en_retard: boolean;
}
interface Option { id: number; libelle: string }

const props = defineProps<{ taches: Tache[]; types: Option[]; priorites: Option[] }>();

const page = usePage();
const succes = computed(() => (page.props.flash as { success?: string } | undefined)?.success);

const form = useForm({
    titre: '',
    type_id: props.types[0]?.id ?? (null as number | null),
    priorite_id: props.priorites[0]?.id ?? (null as number | null),
    echeance_le: '',
    description: '',
});

function creer() {
    form.post('/taches', { preserveScroll: true, onSuccess: () => form.reset('titre', 'echeance_le', 'description') });
}

function terminer(id: number) {
    router.post(`/taches/${id}/terminer`, {}, { preserveScroll: true });
}

function echeance(t: Tache): string {
    if (!t.echeance_le) {
        return 'Sans échéance';
    }
    return new Date(t.echeance_le).toLocaleDateString('fr-MA', { dateStyle: 'medium' });
}

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Tâches', href: '/taches' }],
    },
});
</script>

<template>
    <Head title="Mes tâches" />

    <div class="mx-auto flex w-full max-w-3xl flex-col gap-4 p-4">
        <div v-if="succes" class="rounded-md border border-green-600/30 bg-green-50 px-4 py-2 text-sm text-green-800 dark:bg-green-950/40 dark:text-green-300">
            {{ succes }}
        </div>

        <div class="flex items-baseline justify-between">
            <h1 class="text-xl font-semibold">Mes tâches</h1>
            <span class="text-sm text-muted-foreground">{{ taches.length }} à faire</span>
        </div>

        <!-- Créer une tâche -->
        <form class="flex flex-col gap-3 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border" @submit.prevent="creer">
            <div class="grid gap-3 sm:grid-cols-2">
                <label class="flex flex-col gap-1 text-sm sm:col-span-2">
                    <span>Titre</span>
                    <input v-model="form.titre" type="text" class="rounded-md border bg-background px-3 py-1.5" />
                    <span v-if="form.errors.titre" class="text-destructive">{{ form.errors.titre }}</span>
                </label>
                <label class="flex flex-col gap-1 text-sm">
                    <span>Type</span>
                    <select v-model="form.type_id" class="rounded-md border bg-background px-3 py-1.5">
                        <option v-for="t in types" :key="t.id" :value="t.id">{{ t.libelle }}</option>
                    </select>
                </label>
                <label class="flex flex-col gap-1 text-sm">
                    <span>Priorité</span>
                    <select v-model="form.priorite_id" class="rounded-md border bg-background px-3 py-1.5">
                        <option v-for="p in priorites" :key="p.id" :value="p.id">{{ p.libelle }}</option>
                    </select>
                </label>
                <label class="flex flex-col gap-1 text-sm">
                    <span>Échéance</span>
                    <input v-model="form.echeance_le" type="datetime-local" class="rounded-md border bg-background px-3 py-1.5" />
                </label>
            </div>
            <div>
                <button type="submit" class="rounded-md bg-primary px-3 py-1.5 text-sm font-medium text-primary-foreground hover:opacity-90" :disabled="form.processing">
                    Ajouter
                </button>
            </div>
        </form>

        <!-- Liste (§20 : échues d'abord, sans échéance en dernier) -->
        <ul class="flex flex-col gap-2">
            <li v-for="t in taches" :key="t.id" class="flex items-center justify-between gap-3 rounded-lg border border-sidebar-border/70 p-3 text-sm dark:border-sidebar-border">
                <div class="flex flex-col gap-0.5">
                    <span class="font-medium">{{ t.titre }}</span>
                    <span class="text-xs text-muted-foreground">
                        {{ t.type ?? '—' }} · {{ t.priorite ?? '—' }} ·
                        <span :class="t.en_retard ? 'font-medium text-destructive' : ''">
                            {{ echeance(t) }}<template v-if="t.en_retard"> (en retard)</template>
                        </span>
                    </span>
                </div>
                <button type="button" class="rounded-md border border-sidebar-border/70 px-3 py-1 text-xs font-medium hover:bg-muted dark:border-sidebar-border" @click="terminer(t.id)">
                    Terminer
                </button>
            </li>
            <li v-if="taches.length === 0" class="rounded-lg border border-dashed border-sidebar-border/70 p-6 text-center text-sm text-muted-foreground dark:border-sidebar-border">
                Aucune tâche en cours. Rien ne vous attend.
            </li>
        </ul>
    </div>
</template>
