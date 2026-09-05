import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import Index from './Index.vue';

function ligne(id: number, over: Record<string, unknown> = {}) {
    return { id, numero: `SOC-${id}`, raison_sociale: `Alpha ${id}`, etat: 'Prospect', ville: 'Rabat', proprietaire: 'Hicham', ...over };
}
function monter(props: Record<string, unknown> = {}) {
    return mount(Index, { props: { societes: { data: [ligne(1)], total: 1 }, ...props } });
}

describe('Liste des sociétés', () => {
    it('accorde le décompte au pluriel', () => {
        expect(monter({ societes: { data: [ligne(1)], total: 1 } }).text()).toContain('1 société');
        expect(monter({ societes: { data: [ligne(1)], total: 1 } }).text()).not.toContain('1 sociétés');
        expect(monter({ societes: { data: [ligne(1), ligne(2)], total: 2 } }).text()).toContain('2 sociétés');
    });

    it('nomme l’absence de propriétaire « Non affectée », pas un tiret (§17)', () => {
        const t = monter({ societes: { data: [ligne(1, { proprietaire: null })], total: 1 } }).text();
        expect(t).toContain('Non affectée');
    });

    it('montre l’état vide', () => {
        expect(monter({ societes: { data: [], total: 0 } }).text()).toContain('Aucune');
    });
});
