import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it } from 'vitest';
import { routerMock } from '@/test/preparer';
import Index from './Index.vue';

const base = {
    sources: [{ id: 1, libelle: 'Salon' }],
    campagnes: [{ id: 5, nom: 'Printemps' }],
    lots: [],
};
const apercu = {
    total: 3, importables: 2, rejetees: 1, doublons: 0,
    apercu: [
        { ligne: 1, resultat: 'Importee', raison_sociale: 'Alpha', motif: null },
        { ligne: 2, resultat: 'Rejetee', raison_sociale: null, motif: 'Incomplet' },
        { ligne: 3, resultat: 'Importee', raison_sociale: 'Beta', motif: null },
    ],
};

function monter(props: Record<string, unknown> = {}) {
    return mount(Index, { props: { ...base, ...props } });
}
/** jsdom interdit d'écrire input.files : on le POSE par définition de propriété. */
function poserFichier(input: HTMLInputElement, nom = 'prospects.csv') {
    const f = new File(['x'], nom, { type: 'text/csv' });
    Object.defineProperty(input, 'files', { value: [f], configurable: true });
}

beforeEach(() => {
    routerMock.post.mockClear();
});

describe('Assistant d’import (§42)', () => {
    it('« Analyser » est désactivé tant qu’aucun fichier n’est choisi', () => {
        const boutons = monter().findAll('button');
        const analyser = boutons.find((b) => b.text() === 'Analyser')!;
        expect(analyser.attributes('disabled')).toBeDefined();
    });

    it('analyser poste le fichier vers /import/analyser', async () => {
        const w = monter();
        const input = w.find('input[type="file"]').element as HTMLInputElement;
        poserFichier(input);
        await w.find('input[type="file"]').trigger('change');

        await w.findAll('button').find((b) => b.text() === 'Analyser')!.trigger('click');

        expect(routerMock.post).toHaveBeenCalledTimes(1);
        expect(routerMock.post.mock.calls[0][0]).toBe('/import/analyser');
    });

    it('affiche les compteurs de l’aperçu et les lignes classées', () => {
        const t = monter({ apercu }).text();
        expect(t).toContain('3 lignes');
        expect(t).toContain('2');           // importables
        expect(t).toContain('importables');
        expect(t).toContain('Alpha');
        expect(t).toContain('Rejetee');
        expect(t).toContain('Incomplet');
    });

    it('avertit que la campagne omise est irréversible, et l’avertissement disparaît quand on en choisit une', async () => {
        const w = monter({ apercu });
        expect(w.text()).toContain('ne pourra pas être repris');

        // Choisir une campagne (2e select de l'aperçu).
        const selects = w.findAll('select');
        await selects[1].setValue('5');
        expect(w.text()).not.toContain('ne pourra pas être repris');
    });

    it('importer poste vers /import/executer avec la source et la campagne choisies', async () => {
        const w = monter({ apercu });
        const input = w.find('input[type="file"]').element as HTMLInputElement;
        poserFichier(input);
        await w.find('input[type="file"]').trigger('change');

        const selects = w.findAll('select');
        await selects[0].setValue('1'); // origine
        await selects[1].setValue('5'); // campagne

        await w.findAll('button').find((b) => b.text().includes('Importer'))!.trigger('click');

        expect(routerMock.post).toHaveBeenCalledTimes(1);
        const [url, payload] = routerMock.post.mock.calls[0];
        expect(url).toBe('/import/executer');
        expect(payload).toMatchObject({ source_id: 1, campagne_id: 5 });
    });

    it('affiche « Aucun import réalisé » quand l’historique est vide', () => {
        expect(monter().text()).toContain('Aucun import réalisé');
    });

    it('liste un lot d’historique avec son statut', () => {
        const t = monter({ lots: [{ id: 1, nom_fichier: 'x.csv', statut: 'Termine', total: 5, importees: 4, rejetees: 1, doublons: 0, lance_le: '2026-03-01T09:00:00+00:00' }] }).text();
        expect(t).toContain('x.csv');
        expect(t).toContain('Termine');
    });
});
