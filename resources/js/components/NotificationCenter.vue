<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { Bell } from '@lucide/vue';
import { computed, ref } from 'vue';

interface NotifItem {
    id: number;
    titre: string;
    texte: string | null;
    lue: boolean;
    cree_le: string | null;
}

const page = usePage();
const notifications = computed<NotifItem[]>(() => (page.props.notifications as NotifItem[]) ?? []);
const nonLues = computed<number>(() => (page.props.notificationsNonLues as number) ?? 0);

const ouvert = ref(false);

function ouvrir(n: NotifItem) {
    // §36 : marquer lu PUIS naviguer (le serveur redirige vers la cible).
    router.post(`/notifications/${n.id}/lu`, {}, { preserveScroll: true });
    ouvert.value = false;
}

function toutLire() {
    router.post('/notifications/tout-lu', {}, { preserveScroll: true });
}

function age(iso: string | null): string {
    if (!iso) {
        return '';
    }
    return new Date(iso).toLocaleDateString('fr-MA', { dateStyle: 'short' });
}
</script>

<template>
    <div class="relative">
        <button
            type="button"
            class="relative flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-sm hover:bg-sidebar-accent"
            :aria-label="`Notifications, ${nonLues} non lue${nonLues > 1 ? 's' : ''}`"
            @click="ouvert = !ouvert"
        >
            <Bell class="size-4" />
            <span>Notifications</span>
            <span
                v-if="nonLues > 0"
                class="ml-auto rounded-full bg-primary px-1.5 py-0.5 text-xs font-medium text-primary-foreground"
            >
                {{ nonLues }}
            </span>
        </button>

        <div
            v-if="ouvert"
            class="absolute bottom-full left-0 z-50 mb-1 w-72 rounded-md border border-border bg-popover p-2 text-popover-foreground shadow-lg"
        >
            <div class="flex items-center justify-between px-1 pb-1">
                <span class="text-xs font-semibold text-muted-foreground">Notifications</span>
                <button v-if="nonLues > 0" type="button" class="text-xs text-primary hover:underline" @click="toutLire">
                    Tout marquer lu
                </button>
            </div>

            <ul class="flex max-h-80 flex-col gap-1 overflow-y-auto">
                <li v-for="n in notifications" :key="n.id">
                    <button
                        type="button"
                        class="flex w-full flex-col items-start gap-0.5 rounded-md px-2 py-1.5 text-left text-sm hover:bg-accent"
                        :class="n.lue ? 'opacity-60' : ''"
                        @click="ouvrir(n)"
                    >
                        <span class="flex w-full items-baseline justify-between gap-2">
                            <span class="font-medium">{{ n.titre }}</span>
                            <span class="shrink-0 text-xs text-muted-foreground">{{ age(n.cree_le) }}</span>
                        </span>
                        <span v-if="n.texte" class="text-xs text-muted-foreground">{{ n.texte }}</span>
                        <span v-if="!n.lue" class="text-[10px] font-medium text-primary">Non lue</span>
                    </button>
                </li>
                <li v-if="notifications.length === 0" class="px-2 py-6 text-center text-xs text-muted-foreground">
                    Rien à signaler.
                </li>
            </ul>
        </div>
    </div>
</template>
