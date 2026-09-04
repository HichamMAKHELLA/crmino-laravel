<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface SourceOption { id: number; libelle: string }
interface Doublon {
    id: number; numero: string; raisonSociale: string | null;
    ice: string | null; critere: string; poids: number;
}

defineProps<{ sources: SourceOption[] }>();

const page = usePage();
const doublons = computed<Doublon[]>(
    () => ((page.props.flash as { doublons?: Doublon[] } | undefined)?.doublons ?? []),
);
const erreurRegle = computed(
    () => (page.props.errors as Record<string, string> | undefined)?.rg_lea_001,
);

const form = useForm({
    raison_sociale: '',
    ice: '',
    site_web: '',
    source_id: null as number | null,
    commentaire: '',
    contact: { nom: '', prenom: '', telephone: '', gsm: '', email: '' },
    forcer: false as boolean,
});

function soumettre(forcer: boolean) {
    form.forcer = forcer;
    form.post('/leads', { preserveState: true, preserveScroll: true });
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Prospection', href: '/leads' },
            { title: 'Nouveau lead', href: '/leads/create' },
        ],
    },
});
</script>

<template>
    <Head title="Nouveau lead" />

    <form class="mx-auto flex w-full max-w-2xl flex-col gap-6 p-4" @submit.prevent="soumettre(false)">
        <h1 class="text-xl font-semibold">Nouveau lead</h1>

        <!-- §41/§71 : doublons potentiels. On montre, on ne bloque pas. -->
        <div
            v-if="doublons.length"
            class="rounded-md border border-amber-500/40 bg-amber-50 p-4 text-sm dark:bg-amber-950/30"
        >
            <p class="font-semibold text-amber-900 dark:text-amber-200">
                {{ doublons.length }} fiche{{ doublons.length > 1 ? 's' : '' }} déjà présente{{ doublons.length > 1 ? 's' : '' }} dans le CRM
            </p>
            <ul class="mt-2 space-y-1">
                <li v-for="d in doublons" :key="d.id" class="flex justify-between gap-3">
                    <span>{{ d.raisonSociale ?? d.numero }}</span>
                    <span class="text-amber-800 dark:text-amber-300">{{ d.critere }}</span>
                </li>
            </ul>
            <button
                type="button"
                class="mt-3 rounded-md border border-amber-600/50 px-3 py-1.5 font-medium text-amber-900 hover:bg-amber-100 dark:text-amber-200 dark:hover:bg-amber-900/40"
                :disabled="form.processing"
                @click="soumettre(true)"
            >
                Créer quand même
            </button>
        </div>

        <p v-if="erreurRegle" class="rounded-md bg-destructive/10 px-3 py-2 text-sm text-destructive">
            {{ erreurRegle }}
        </p>

        <fieldset class="flex flex-col gap-4 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
            <legend class="px-1 text-sm font-medium text-muted-foreground">Entreprise</legend>
            <label class="flex flex-col gap-1 text-sm">
                <span>Raison sociale</span>
                <input v-model="form.raison_sociale" type="text" class="rounded-md border bg-background px-3 py-1.5" />
            </label>
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="flex flex-col gap-1 text-sm">
                    <span>ICE</span>
                    <input v-model="form.ice" type="text" class="rounded-md border bg-background px-3 py-1.5" />
                </label>
                <label class="flex flex-col gap-1 text-sm">
                    <span>Site web</span>
                    <input v-model="form.site_web" type="text" placeholder="exemple.ma" class="rounded-md border bg-background px-3 py-1.5" />
                </label>
            </div>
            <label class="flex flex-col gap-1 text-sm">
                <span>Origine <span class="text-destructive">*</span></span>
                <select v-model="form.source_id" class="rounded-md border bg-background px-3 py-1.5">
                    <option :value="null" disabled>— Choisir —</option>
                    <option v-for="s in sources" :key="s.id" :value="s.id">{{ s.libelle }}</option>
                </select>
                <span v-if="form.errors.source_id" class="text-destructive">{{ form.errors.source_id }}</span>
            </label>
        </fieldset>

        <fieldset class="flex flex-col gap-4 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
            <legend class="px-1 text-sm font-medium text-muted-foreground">Contact</legend>
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="flex flex-col gap-1 text-sm">
                    <span>Nom</span>
                    <input v-model="form.contact.nom" type="text" class="rounded-md border bg-background px-3 py-1.5" />
                </label>
                <label class="flex flex-col gap-1 text-sm">
                    <span>Prénom</span>
                    <input v-model="form.contact.prenom" type="text" class="rounded-md border bg-background px-3 py-1.5" />
                </label>
                <label class="flex flex-col gap-1 text-sm">
                    <span>Téléphone</span>
                    <input v-model="form.contact.telephone" type="text" class="rounded-md border bg-background px-3 py-1.5" />
                </label>
                <label class="flex flex-col gap-1 text-sm">
                    <span>GSM</span>
                    <input v-model="form.contact.gsm" type="text" class="rounded-md border bg-background px-3 py-1.5" />
                </label>
                <label class="flex flex-col gap-1 text-sm sm:col-span-2">
                    <span>Courriel</span>
                    <input v-model="form.contact.email" type="email" class="rounded-md border bg-background px-3 py-1.5" />
                </label>
            </div>
        </fieldset>

        <div class="flex items-center gap-3">
            <button
                type="submit"
                class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:opacity-90"
                :disabled="form.processing"
            >
                Créer le lead
            </button>
            <a href="/leads" class="text-sm text-muted-foreground hover:underline">Annuler</a>
        </div>
    </form>
</template>
