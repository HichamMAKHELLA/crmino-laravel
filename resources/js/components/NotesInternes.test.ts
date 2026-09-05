import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it } from 'vitest';
import { routerMock } from '@/test/preparer';
import NotesInternes from './NotesInternes.vue';

function note(over: Record<string, unknown> = {}) {
    return {
        id: 1, texte: 'Client à rappeler', auteur: 'Ahmed', auteur_id: 7,
        cree_le: '2026-03-01T09:00:00+00:00', modifie_le: null, ...over,
    };
}
function monter(props: Record<string, unknown> = {}) {
    return mount(NotesInternes, {
        props: { cibleType: 'Societe', cibleId: 42, moiId: 7, notes: [note()], ...props },
    });
}

beforeEach(() => {
    routerMock.post.mockClear();
    routerMock.put.mockClear();
    routerMock.delete.mockClear();
});

describe('NotesInternes (§45)', () => {
    it('rend le texte de la note', () => {
        expect(monter().text()).toContain('Client à rappeler');
    });

    it('affiche « Aucune note » quand la liste est vide', () => {
        expect(monter({ notes: [] }).text()).toContain('Aucune note interne');
    });

    it('offre corriger/retirer à l’AUTEUR seulement', () => {
        // moiId 7 = auteur_id 7 -> les commandes sont là.
        expect(monter().text()).toContain('Corriger');
        // moiId différent -> pas de commandes.
        const autrui = monter({ moiId: 999 });
        expect(autrui.text()).not.toContain('Corriger');
        expect(autrui.text()).not.toContain('Retirer');
    });

    it('rend le texte comme du TEXTE (pas d’injection HTML)', () => {
        const w = monter({ notes: [note({ texte: '<img src=x onerror=alert(1)>' })] });
        // Aucune balise <img> réellement insérée : le contenu est échappé.
        expect(w.find('img').exists()).toBe(false);
        expect(w.text()).toContain('<img src=x onerror=alert(1)>');
    });

    it('poste une nouvelle note avec la bonne cible', async () => {
        const w = monter();
        await w.find('textarea').setValue('Nouvelle note');
        await w.find('form').trigger('submit.prevent');

        expect(routerMock.post).toHaveBeenCalledTimes(1);
        const [url, payload] = routerMock.post.mock.calls[0];
        expect(url).toBe('/commentaires');
        expect(payload).toMatchObject({ cible_type: 'Societe', cible_id: 42, texte: 'Nouvelle note' });
    });

    it('ne poste PAS une note vide', async () => {
        const w = monter();
        await w.find('textarea').setValue('   ');
        await w.find('form').trigger('submit.prevent');
        expect(routerMock.post).not.toHaveBeenCalled();
    });

    it('n’affiche le compteur qu’à l’approche de la limite', async () => {
        const w = monter();
        const zone = w.find('textarea');
        await zone.setValue('court');
        expect(w.text()).not.toContain('/ 4000');
        await zone.setValue('x'.repeat(3700));
        expect(w.text()).toContain('/ 4000');
    });

    it('marque « modifiée le » quand modifie_le est renseigné', () => {
        expect(monter({ notes: [note({ modifie_le: '2026-03-02T10:00:00+00:00' })] }).text())
            .toContain('modifiée le');
    });
});
