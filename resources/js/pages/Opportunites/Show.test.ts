import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { routerMock } from '@/test/preparer';
import Show from './Show.vue';

function opp(over: Record<string, unknown> = {}) {
    return {
        id: 3, numero: 'OPP-2026-00003', intitule: 'Grosse affaire', statut: 'Ouverte',
        etape: 'Proposition', probabilite: 40, montant_ht: 200000, montant_pondere: 80000,
        motif_perte: null, commentaire_perte: null, proprietaire: 'Hicham', ...over,
    };
}
function monter(props: Record<string, unknown> = {}) {
    return mount(Show, {
        props: {
            opportunite: opp(), societe: { id: 1, numero: 'SOC-1', raison_sociale: 'Alpha', etat: 'Prospect' },
            peutCloturer: true, peutModifier: true, lignes: [], catalogue: [],
            motifs: [{ id: 1, libelle: 'Prix', commentaire_obligatoire: false }], ...props,
        },
    });
}

beforeEach(() => {
    routerMock.post.mockClear();
    routerMock.put.mockClear();
});

describe('Fiche opportunité (§25)', () => {
    it('affiche l’en-tête et les chiffres', () => {
        const t = monter().text();
        expect(t).toContain('Grosse affaire');
        expect(t).toContain('Proposition');
        expect(t).toContain('Alpha');
    });

    it('sur une affaire ouverte, propose gagner et la clôture en perte', () => {
        const t = monter().text();
        expect(t).toContain('Marquer gagnée');
        expect(t).toContain('Clôturer en perte');
    });

    it('sur une affaire CLOSE, montre les verrous et cache la clôture', () => {
        const t = monter({ opportunite: opp({ statut: 'Perdue', motif_perte: 'Prix' }), peutCloturer: true }).text();
        expect(t).toContain('close'); // bandeau RG-OPP-005
        expect(t).toContain('Prix');  // motif affiché
        expect(t).not.toContain('Marquer gagnée');
    });

    it('le panneau de lignes est éditable sur une affaire ouverte', () => {
        expect(monter().text()).toContain('Ajouter une ligne');
    });

    it('le panneau de lignes est en lecture seule sur une affaire close', () => {
        const w = monter({ opportunite: opp({ statut: 'Gagnee' }), lignes: [{ id: 1, designation: 'P', produit: 'P', quantite: 1, unite: null, prix_unitaire: 1000, montant_ht: 1000 }] });
        expect(w.text()).not.toContain('Ajouter une ligne');
        expect(w.text()).toContain('close');
    });

    it('« Modifier » ouvre le formulaire, qui soumet un PUT vers l’affaire', async () => {
        const w = monter();
        await w.findAll('button').find((b) => b.text() === 'Modifier')!.trigger('click');
        expect(w.text()).toContain('Intitulé');

        await w.find('form').trigger('submit.prevent');
        expect(routerMock.put).toHaveBeenCalledWith('/opportunites/3', expect.anything());
    });

    it('« Marquer gagnée » poste vers la route de gain (après confirmation)', async () => {
        // confirm() renvoie true par défaut dans jsdom ? Non : on le force.
        vi.stubGlobal('confirm', () => true);
        const w = monter();
        await w.findAll('button').find((b) => b.text() === 'Marquer gagnée')!.trigger('click');
        expect(routerMock.post).toHaveBeenCalledWith('/opportunites/3/gagner');
    });
});
