import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it } from 'vitest';
import { routerMock } from '@/test/preparer';
import Index from './Index.vue';

function carte(id: number, etapeId: number) {
    return { id, etape_id: etapeId, intitule: `Affaire ${id}`, societe: 'Alpha', montant_ht: 100000, montant_pondere: 50000, probabilite: 50 };
}
function monter() {
    return mount(Index, {
        props: {
            colonnes: [
                { id: 1, libelle: 'Qualification', couleur: null, total_pondere: 50000, cartes: [carte(10, 1)] },
                { id: 2, libelle: 'Proposition', couleur: null, total_pondere: 0, cartes: [] },
            ],
        },
    });
}

beforeEach(() => routerMock.post.mockClear());

describe('Pipeline / Kanban (§64)', () => {
    it('rend une colonne par étape et les cartes', () => {
        const t = monter().text();
        expect(t).toContain('Qualification');
        expect(t).toContain('Proposition');
        expect(t).toContain('Affaire 10');
    });

    it('glisser une carte vers une autre colonne poste le déplacement d’étape', async () => {
        const w = monter();
        const cartes = w.findAll('a[draggable="true"]');
        expect(cartes).toHaveLength(1);
        const colonnes = w.findAll('.w-72');

        await cartes[0].trigger('dragstart');       // debut(carte 10)
        await colonnes[1].trigger('drop');           // deposer(colonne 2)

        expect(routerMock.post).toHaveBeenCalledTimes(1);
        const [url, payload] = routerMock.post.mock.calls[0];
        expect(url).toBe('/opportunites/10/etape');
        expect(payload).toEqual({ etape_id: 2 });
    });

    it('déplace la carte OPTIMISTE avant la confirmation serveur', async () => {
        const w = monter();
        const cartes = w.findAll('a[draggable="true"]');
        const colonnes = w.findAll('.w-72');

        await cartes[0].trigger('dragstart');
        await colonnes[1].trigger('drop');

        // La carte a quitté la colonne 1 pour la colonne 2, sans attendre le serveur.
        expect(colonnes[0].text()).toContain('Aucune affaire');
        expect(colonnes[1].text()).toContain('Affaire 10');
    });

    it('déposer sur la MÊME colonne ne poste rien', async () => {
        const w = monter();
        const cartes = w.findAll('a[draggable="true"]');
        const colonnes = w.findAll('.w-72');

        await cartes[0].trigger('dragstart');
        await colonnes[0].trigger('drop'); // même colonne d'origine

        expect(routerMock.post).not.toHaveBeenCalled();
    });

    it('affiche un message quand aucune étape n’est configurée', () => {
        const w = mount(Index, { props: { colonnes: [] } });
        expect(w.text()).toContain('Aucune étape de pipeline configurée');
    });
});
