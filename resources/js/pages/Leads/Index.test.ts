import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import Index from './Index.vue';

function ligne(id: number, over: Record<string, unknown> = {}) {
    return { id, numero: `LEAD-${id}`, raison_sociale: `Boîte ${id}`, statut: 'Nouveau', source: 'Salon', ville: 'Casablanca', ...over };
}
function monter(props: Record<string, unknown> = {}) {
    return mount(Index, { props: { leads: { data: [ligne(1)], total: 1 }, peutCreer: true, ...props } });
}

describe('Liste de prospection', () => {
    it('rend les lignes avec un lien vers la fiche', () => {
        const w = monter();
        expect(w.text()).toContain('Boîte 1');
        expect(w.find('a[href="/leads/1"]').exists()).toBe(true);
    });

    it('accorde le décompte au pluriel (le cas ordinaire est 1)', () => {
        expect(monter({ leads: { data: [ligne(1)], total: 1 } }).text()).toContain('1 lead');
        expect(monter({ leads: { data: [ligne(1)], total: 1 } }).text()).not.toContain('1 leads');
        expect(monter({ leads: { data: [ligne(1), ligne(2)], total: 2 } }).text()).toContain('2 leads');
    });

    it('affiche « — » pour une raison sociale absente (lead au téléphone)', () => {
        expect(monter({ leads: { data: [ligne(1, { raison_sociale: null })], total: 1 } }).text()).toContain('—');
    });

    it('montre l’état vide quand il n’y a aucun lead', () => {
        expect(monter({ leads: { data: [], total: 0 } }).text()).toContain('Aucun lead dans votre périmètre');
    });

    it('n’offre « Nouveau lead » qu’avec le droit de créer', () => {
        expect(monter({ peutCreer: true }).find('a[href="/leads/create"]').exists()).toBe(true);
        expect(monter({ peutCreer: false }).find('a[href="/leads/create"]').exists()).toBe(false);
    });
});
