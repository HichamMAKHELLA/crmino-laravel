<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';

interface GammeOption { id: number; libelle: string }

const props = defineProps<{ gammes: GammeOption[]; types: string[] }>();

const form = useForm({
    gamme_id: props.gammes[0]?.id ?? (null as number | null),
    code: '',
    designation: '',
    type: props.types[0] ?? 'Licence',
    prix_catalogue: null as number | null,
    unite: '',
});

function creer() {
    form.post('/catalogue');
}

defineOptions({
    layout: { breadcrumbs: [{ title: 'Catalogue', href: '/catalogue' }, { title: 'Nouveau produit', href: '/catalogue/create' }] },
});
</script>

<template>
    <Head title="Nouveau produit" />

    <form class="mx-auto flex w-full max-w-2xl flex-col gap-6 p-4" @submit.prevent="creer">
        <h1 class="text-xl font-semibold">Nouveau produit</h1>

        <div class="flex flex-col gap-4 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
            <label class="flex flex-col gap-1 text-sm">
                <span>Gamme <span class="text-destructive">*</span></span>
                <select v-model="form.gamme_id" class="rounded-md border bg-background px-3 py-1.5">
                    <option v-for="g in gammes" :key="g.id" :value="g.id">{{ g.libelle }}</option>
                </select>
            </label>
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="flex flex-col gap-1 text-sm">
                    <span>Code <span class="text-destructive">*</span></span>
                    <input v-model="form.code" type="text" class="rounded-md border bg-background px-3 py-1.5 font-mono uppercase" />
                    <span v-if="form.errors.code" class="text-destructive">{{ form.errors.code }}</span>
                </label>
                <label class="flex flex-col gap-1 text-sm">
                    <span>Type <span class="text-destructive">*</span></span>
                    <select v-model="form.type" class="rounded-md border bg-background px-3 py-1.5">
                        <option v-for="t in types" :key="t" :value="t">{{ t }}</option>
                    </select>
                </label>
                <label class="flex flex-col gap-1 text-sm sm:col-span-2">
                    <span>Désignation <span class="text-destructive">*</span></span>
                    <input v-model="form.designation" type="text" class="rounded-md border bg-background px-3 py-1.5" />
                    <span v-if="form.errors.designation" class="text-destructive">{{ form.errors.designation }}</span>
                </label>
                <label class="flex flex-col gap-1 text-sm">
                    <span>Prix catalogue (MAD)</span>
                    <input v-model.number="form.prix_catalogue" type="number" min="0" class="rounded-md border bg-background px-3 py-1.5" />
                </label>
                <label class="flex flex-col gap-1 text-sm">
                    <span>Unité</span>
                    <input v-model="form.unite" type="text" placeholder="licence, jour…" class="rounded-md border bg-background px-3 py-1.5" />
                </label>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90" :disabled="form.processing">
                Ajouter au catalogue
            </button>
            <a href="/catalogue" class="text-sm text-muted-foreground hover:underline">Annuler</a>
        </div>
    </form>
</template>
