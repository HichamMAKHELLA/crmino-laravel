import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import Index from './Index.vue';

function ligne(id: number, over: Record<string, unknown> = {}) {
    return { id, numero: `CAM-${id}`, nom: `Campagne ${id}`, type: 'Emailing', statut: 'EnCours', budget: 12000, nb_leads: 5, responsable: 'Hicham', ...over };
}
function monter(props: Record<string, unknown> = {}) {
    return mount(Index, { props: { campagnes: { data: [ligne(1)], total: 1 }, ...props } });
}

describe('Liste des campagnes', () => {
    it('rend les lignes avec un lien vers la fiche', () => {
        const w = monter();
        expect(w.text()).toContain('Campagne 1');
        expect(w.find('a[href="/campagnes/1"]').exists()).toBe(true);
    });

    it('RG-IND-002 — un budget absent s’affiche « Non chiffré », jamais 0', () => {
        const t = monter({ campagnes: { data: [ligne(1, { budget: null })], total: 1 } }).text();
        expect(t).toContain('Non chiffré');
        expect(t).not.toContain('0,00');
    });

    it('accorde le décompte au pluriel', () => {
        expect(monter({ campagnes: { data: [ligne(1)], total: 1 } }).text()).toContain('1 campagne');
        expect(monter({ campagnes: { data: [ligne(1)], total: 1 } }).text()).not.toContain('1 campagnes');
        expect(monter({ campagnes: { data: [ligne(1), ligne(2)], total: 2 } }).text()).toContain('2 campagnes');
    });
});
