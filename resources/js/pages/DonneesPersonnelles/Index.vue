<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

interface ContactLigne { id: number; nom: string; email: string | null }

const props = defineProps<{ q: string; contacts: ContactLigne[] }>();

const q = ref(props.q ?? '');
function chercher() {
    router.get('/donnees-personnelles', { q: q.value }, { preserveState: true, preserveScroll: true });
}
function produire(id: number) {
    router.post(`/donnees-personnelles/contact/${id}`);
}

defineOptions({ layout: { breadcrumbs: [{ title: 'Données personnelles', href: '/donnees-personnelles' }] } });
</script>

<template>
    <Head title="Droit d'accès (§72)" />

    <div class="mx-auto flex w-full max-w-2xl flex-col gap-4 p-4">
        <h1 class="text-xl font-semibold">Droit d'accès (§72)</h1>
        <p class="text-sm text-muted-foreground">
            Produisez le dossier de données personnelles d'un contact. Chaque production laisse une trace d'audit.
        </p>

        <form class="flex gap-2" @submit.prevent="chercher">
            <input v-model="q" type="text" placeholder="Nom ou courriel du contact…" class="flex-1 rounded-md border bg-background px-3 py-1.5 text-sm" />
            <button type="submit" class="rounded-md bg-primary px-3 py-1.5 text-sm font-medium text-primary-foreground hover:opacity-90">Rechercher</button>
        </form>

        <ul class="flex flex-col gap-2">
            <li v-for="c in contacts" :key="c.id" class="flex items-center justify-between gap-3 rounded-lg border border-sidebar-border/70 p-3 text-sm dark:border-sidebar-border">
                <div>
                    <p class="font-medium">{{ c.nom }}</p>
                    <p class="text-xs text-muted-foreground">{{ c.email ?? '—' }}</p>
                </div>
                <button type="button" class="rounded-md border border-sidebar-border/70 px-3 py-1 text-xs font-medium hover:bg-muted dark:border-sidebar-border" @click="produire(c.id)">
                    Produire le dossier
                </button>
            </li>
            <li v-if="q.length >= 2 && contacts.length === 0" class="rounded-lg border border-dashed border-sidebar-border/70 p-6 text-center text-sm text-muted-foreground dark:border-sidebar-border">
                Aucun contact trouvé.
            </li>
        </ul>
    </div>
</template>
