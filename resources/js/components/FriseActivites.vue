<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';

interface ActiviteLigne {
    id: number;
    type: string | null;
    objet: string | null;
    resultat: string | null;
    debut_le: string;
    utilisateur: string | null;
    prochaine_action_le: string | null;
    prochaine_action_libelle: string | null;
}
interface TypeOption { id: number; libelle: string }

const props = defineProps<{
    cibleType: 'lead' | 'societe';
    cibleId: number;
    activites: ActiviteLigne[];
    types: TypeOption[];
}>();

function maintenantLocal(): string {
    // Heure murale locale au format datetime-local (l'API la convertit).
    const d = new Date();
    const p = (n: number) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())}T${p(d.getHours())}:${p(d.getMinutes())}`;
}

const form = useForm({
    cible_type: props.cibleType,
    cible_id: props.cibleId,
    type_id: props.types[0]?.id ?? (null as number | null),
    debut_le: maintenantLocal(),
    objet: '',
    resultat: '',
    prochaine_action_le: '',
    prochaine_action_libelle: '',
});

function enregistrer() {
    form.post('/activites', {
        preserveScroll: true,
        onSuccess: () => form.reset('objet', 'resultat', 'prochaine_action_le', 'prochaine_action_libelle'),
    });
}

function dateCourte(iso: string): string {
    return new Date(iso).toLocaleString('fr-MA', { dateStyle: 'medium', timeStyle: 'short' });
}
</script>

<template>
    <section class="flex flex-col gap-3">
        <h2 class="text-sm font-semibold text-muted-foreground">Activités (§73)</h2>

        <!-- Journaliser une activité -->
        <form class="flex flex-col gap-3 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border" @submit.prevent="enregistrer">
            <div class="grid gap-3 sm:grid-cols-2">
                <label class="flex flex-col gap-1 text-sm">
                    <span>Type</span>
                    <select v-model="form.type_id" class="rounded-md border bg-background px-3 py-1.5">
                        <option v-for="t in types" :key="t.id" :value="t.id">{{ t.libelle }}</option>
                    </select>
                </label>
                <label class="flex flex-col gap-1 text-sm">
                    <span>Date</span>
                    <input v-model="form.debut_le" type="datetime-local" class="rounded-md border bg-background px-3 py-1.5" />
                </label>
                <label class="flex flex-col gap-1 text-sm sm:col-span-2">
                    <span>Objet</span>
                    <input v-model="form.objet" type="text" class="rounded-md border bg-background px-3 py-1.5" />
                </label>
                <label class="flex flex-col gap-1 text-sm">
                    <span>Prochaine action</span>
                    <input v-model="form.prochaine_action_libelle" type="text" class="rounded-md border bg-background px-3 py-1.5" />
                </label>
                <label class="flex flex-col gap-1 text-sm">
                    <span>Le</span>
                    <input v-model="form.prochaine_action_le" type="datetime-local" class="rounded-md border bg-background px-3 py-1.5" />
                </label>
            </div>
            <div>
                <button type="submit" class="rounded-md bg-primary px-3 py-1.5 text-sm font-medium text-primary-foreground hover:opacity-90" :disabled="form.processing">
                    Enregistrer l'activité
                </button>
            </div>
        </form>

        <!-- Frise -->
        <ol class="flex flex-col gap-2">
            <li v-for="a in activites" :key="a.id" class="rounded-lg border border-sidebar-border/70 p-3 text-sm dark:border-sidebar-border">
                <div class="flex items-baseline justify-between gap-2">
                    <span class="font-medium">{{ a.type ?? 'Activité' }}<template v-if="a.objet"> — {{ a.objet }}</template></span>
                    <span class="text-xs text-muted-foreground">{{ dateCourte(a.debut_le) }}</span>
                </div>
                <p v-if="a.resultat" class="mt-1 text-muted-foreground">{{ a.resultat }}</p>
                <p v-if="a.prochaine_action_libelle" class="mt-1 text-xs text-primary">
                    Prochaine action : {{ a.prochaine_action_libelle }}
                    <template v-if="a.prochaine_action_le"> — {{ dateCourte(a.prochaine_action_le) }}</template>
                </p>
            </li>
            <li v-if="activites.length === 0" class="rounded-lg border border-dashed border-sidebar-border/70 p-4 text-center text-sm text-muted-foreground dark:border-sidebar-border">
                Aucune activité. La première relance est à consigner.
            </li>
        </ol>
    </section>
</template>
