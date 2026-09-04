<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';

interface Option { id: number; libelle: string }
interface UserOption { id: number; nom: string }

const props = defineProps<{ types: Option[]; responsables: UserOption[] }>();

const form = useForm({
    nom: '',
    type_campagne_id: props.types[0]?.id ?? (null as number | null),
    date_debut: '',
    date_fin: '',
    responsable_id: props.responsables[0]?.id ?? (null as number | null),
    budget: null as number | null,
    cible: '',
});

function creer() {
    form.post('/campagnes');
}

defineOptions({
    layout: { breadcrumbs: [{ title: 'Campagnes', href: '/campagnes' }, { title: 'Nouvelle campagne', href: '/campagnes/create' }] },
});
</script>

<template>
    <Head title="Nouvelle campagne" />

    <form class="mx-auto flex w-full max-w-2xl flex-col gap-6 p-4" @submit.prevent="creer">
        <h1 class="text-xl font-semibold">Nouvelle campagne</h1>

        <div class="flex flex-col gap-4 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
            <label class="flex flex-col gap-1 text-sm">
                <span>Nom <span class="text-destructive">*</span></span>
                <input v-model="form.nom" type="text" class="rounded-md border bg-background px-3 py-1.5" />
                <span v-if="form.errors.nom" class="text-destructive">{{ form.errors.nom }}</span>
            </label>

            <div class="grid gap-4 sm:grid-cols-2">
                <label class="flex flex-col gap-1 text-sm">
                    <span>Type <span class="text-destructive">*</span></span>
                    <select v-model="form.type_campagne_id" class="rounded-md border bg-background px-3 py-1.5">
                        <option v-for="t in types" :key="t.id" :value="t.id">{{ t.libelle }}</option>
                    </select>
                </label>
                <label class="flex flex-col gap-1 text-sm">
                    <span>Responsable <span class="text-destructive">*</span></span>
                    <select v-model="form.responsable_id" class="rounded-md border bg-background px-3 py-1.5">
                        <option v-for="u in responsables" :key="u.id" :value="u.id">{{ u.nom }}</option>
                    </select>
                </label>
                <label class="flex flex-col gap-1 text-sm">
                    <span>Début <span class="text-destructive">*</span></span>
                    <input v-model="form.date_debut" type="date" class="rounded-md border bg-background px-3 py-1.5" />
                    <span v-if="form.errors.date_debut" class="text-destructive">{{ form.errors.date_debut }}</span>
                </label>
                <label class="flex flex-col gap-1 text-sm">
                    <span>Fin</span>
                    <input v-model="form.date_fin" type="date" class="rounded-md border bg-background px-3 py-1.5" />
                    <span v-if="form.errors.date_fin" class="text-destructive">{{ form.errors.date_fin }}</span>
                </label>
                <label class="flex flex-col gap-1 text-sm">
                    <span>Budget (MAD)</span>
                    <input v-model.number="form.budget" type="number" min="0" class="rounded-md border bg-background px-3 py-1.5" />
                </label>
                <label class="flex flex-col gap-1 text-sm">
                    <span>Cible</span>
                    <input v-model="form.cible" type="text" class="rounded-md border bg-background px-3 py-1.5" />
                </label>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90" :disabled="form.processing">
                Créer la campagne
            </button>
            <a href="/campagnes" class="text-sm text-muted-foreground hover:underline">Annuler</a>
        </div>
    </form>
</template>
