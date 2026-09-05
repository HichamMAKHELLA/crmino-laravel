import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it } from 'vitest';
import { routerMock } from '@/test/preparer';
import Index from './Index.vue';

function tache(over: Record<string, unknown> = {}) {
    return { id: 1, titre: 'Rappeler Alpha', type: 'Appel', priorite: 'Haute', echeance_le: '2026-03-10T09:00:00+00:00', en_retard: false, ...over };
}
function monter(props: Record<string, unknown> = {}) {
    return mount(Index, {
        props: {
            taches: [tache()], types: [{ id: 1, libelle: 'Appel' }],
            priorites: [{ id: 1, libelle: 'Haute' }], utilisateurs: [{ id: 2, nom: 'Salma' }], ...props,
        },
    });
}

beforeEach(() => {
    routerMock.post.mockClear();
});

describe('Mes tâches (§20)', () => {
    it('rend les tâches avec titre, type et priorité', () => {
        const t = monter().text();
        expect(t).toContain('Rappeler Alpha');
        expect(t).toContain('Appel');
        expect(t).toContain('Haute');
    });

    it('dit « Sans échéance » quand il n’y a pas de date (pas une case vide)', () => {
        expect(monter({ taches: [tache({ echeance_le: null })] }).text()).toContain('Sans échéance');
    });

    it('signale « en retard » par un MOT, pas seulement une couleur', () => {
        expect(monter({ taches: [tache({ en_retard: true })] }).text()).toContain('en retard');
        expect(monter({ taches: [tache({ en_retard: false })] }).text()).not.toContain('en retard');
    });

    it('terminer passe par SA route (POST /taches/{id}/terminer)', async () => {
        const w = monter();
        await w.findAll('button').find((b) => b.text() === 'Terminer')!.trigger('click');
        expect(routerMock.post).toHaveBeenCalledWith('/taches/1/terminer', {}, expect.anything());
    });

    it('créer une tâche poste vers /taches', async () => {
        const w = monter();
        await w.find('form').trigger('submit.prevent');
        expect(routerMock.post).toHaveBeenCalledWith('/taches', expect.anything());
    });

    it('affiche l’état vide quand il n’y a rien à faire', () => {
        expect(monter({ taches: [] }).text()).toContain('à faire');
    });
});
