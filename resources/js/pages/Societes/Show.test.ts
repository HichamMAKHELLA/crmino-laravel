import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it } from 'vitest';
import { routerMock } from '@/test/preparer';
import Show from './Show.vue';

const stubs = { FriseActivites: true, NotesInternes: true, Documents: true };

function societe(over: Record<string, unknown> = {}) {
    return {
        id: 1, numero: 'SOC-2026-00001', raison_sociale: 'Alpha SARL', etat: 'Prospect',
        ice: '001', site_web: null, ville: null, source: 'Salon', devenu_client_le: null, proprietaire: 'Hicham', ...over,
    };
}
const champs = { ice: '001', site_web: null, telephone: null, email: null, adresse: null, commentaire: null };
function monter(props: Record<string, unknown> = {}) {
    return mount(Show, {
        global: { stubs },
        props: {
            societe: societe(), contacts: [], activites: [], typesActivite: [],
            ciblesEtat: ['Client', 'Inactif'], notes: [], moiId: 7,
            documents: [], typesDocument: [], peutDeposer: true, peutSupprimer: true,
            peutModifier: true, champs, ...props,
        },
    });
}

beforeEach(() => {
    routerMock.post.mockClear();
    routerMock.put.mockClear();
});

describe('Fiche société (§18 / §32)', () => {
    it('affiche la raison sociale et l’état', () => {
        const t = monter().text();
        expect(t).toContain('Alpha SARL');
        expect(t).toContain('Prospect');
    });

    it('offre les cibles d’état (RG-SOC-001) et poste le changement', async () => {
        const w = monter();
        // Le select d'état porte les cibles Client/Inactif.
        const select = w.findAll('select').find((s) => s.text().includes('Inactif'))!;
        await select.setValue('Inactif');
        await w.findAll('button').find((b) => b.text().includes('Changer'))!.trigger('click');

        expect(routerMock.post).toHaveBeenCalledWith('/societes/1/etat', { etat: 'Inactif' }, expect.anything());
    });

    it('sans droit de modifier, ni bouton Modifier ni sélecteur d’état', () => {
        const w = monter({ peutModifier: false, ciblesEtat: [] });
        expect(w.findAll('button').some((b) => b.text() === 'Modifier')).toBe(false);
        expect(w.text()).not.toContain('Changer');
    });

    it('« Modifier » ouvre le formulaire, qui soumet un PUT vers la société', async () => {
        const w = monter();
        await w.findAll('button').find((b) => b.text() === 'Modifier')!.trigger('click');
        expect(w.text()).toContain('Raison sociale');

        await w.find('form').trigger('submit.prevent');
        expect(routerMock.put).toHaveBeenCalledWith('/societes/1', expect.anything());
    });

    it('monte les panneaux Documents et Notes internes', () => {
        const w = monter();
        expect(w.findComponent({ name: 'Documents' }).exists()).toBe(true);
        expect(w.findComponent({ name: 'NotesInternes' }).exists()).toBe(true);
    });
});
