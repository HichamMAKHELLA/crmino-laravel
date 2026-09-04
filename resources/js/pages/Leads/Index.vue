<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface LigneLead {
    id: number;
    numero: string;
    raison_sociale: string | null;
    statut: string | null;
    source: string | null;
    ville: string | null;
}

defineProps<{
    leads: { data: LigneLead[]; total: number };
    peutCreer: boolean;
}>();

const page = usePage();
const succes = computed(() => (page.props.flash as { success?: string } | undefined)?.success);

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Prospection', href: '/leads' }],
    },
});
</script>

<template>
    <Head title="Prospection" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <div
            v-if="succes"
            class="rounded-md border border-green-600/30 bg-green-50 px-4 py-2 text-sm text-green-800 dark:bg-green-950/40 dark:text-green-300"
        >
            {{ succes }}
        </div>

        <div class="flex items-baseline justify-between">
            <h1 class="text-xl font-semibold">Prospection</h1>
            <div class="flex items-center gap-3">
                <span class="text-sm text-muted-foreground">{{ leads.total }} lead{{ leads.total > 1 ? 's' : '' }}</span>
                <Link
                    v-if="peutCreer"
                    href="/leads/create"
                    class="rounded-md bg-primary px-3 py-1.5 text-sm font-medium text-primary-foreground hover:opacity-90"
                >
                    + Nouveau lead
                </Link>
            </div>
        </div>

        <div class="overflow-x-auto rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left text-muted-foreground">
                    <tr>
                        <th class="px-4 py-2 font-medium">Numéro</th>
                        <th class="px-4 py-2 font-medium">Raison sociale</th>
                        <th class="px-4 py-2 font-medium">Statut</th>
                        <th class="px-4 py-2 font-medium">Ville</th>
                        <th class="px-4 py-2 font-medium">Origine</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="lead in leads.data" :key="lead.id" class="border-t border-sidebar-border/50">
                        <td class="px-4 py-2 font-mono text-xs"><Link :href="`/leads/${lead.id}`" class="text-primary hover:underline">{{ lead.numero }}</Link></td>
                        <td class="px-4 py-2">{{ lead.raison_sociale ?? '—' }}</td>
                        <td class="px-4 py-2">{{ lead.statut ?? '—' }}</td>
                        <td class="px-4 py-2">{{ lead.ville ?? '—' }}</td>
                        <td class="px-4 py-2">{{ lead.source ?? '—' }}</td>
                    </tr>
                    <tr v-if="leads.data.length === 0">
                        <td colspan="5" class="px-4 py-8 text-center text-muted-foreground">
                            Aucun lead dans votre périmètre.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
