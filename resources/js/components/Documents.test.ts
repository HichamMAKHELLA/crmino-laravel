import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it } from 'vitest';
import { routerMock } from '@/test/preparer';
import Documents from './Documents.vue';

function doc(over: Record<string, unknown> = {}) {
    return { id: 3, nom: 'Devis.pdf', type: 'Devis', taille_octets: 2048, depose_le: '2026-03-01T09:00:00+00:00', ...over };
}
function monter(props: Record<string, unknown> = {}) {
    return mount(Documents, {
        props: {
            cibleType: 'Societe', cibleId: 42, documents: [doc()],
            typesDocument: [{ id: 1, libelle: 'Devis' }], peutDeposer: true, peutSupprimer: true, ...props,
        },
    });
}

beforeEach(() => routerMock.delete.mockClear());

describe('Documents (§44)', () => {
    it('liste un document avec un lien de téléchargement en pièce jointe', () => {
        const lien = monter().find('a');
        expect(lien.attributes('href')).toBe('/documents/3/telecharger');
        expect(lien.text()).toBe('Devis.pdf');
    });

    it('formate la taille lisiblement', () => {
        expect(monter().text()).toContain('2 Ko');
        expect(monter({ documents: [doc({ taille_octets: 500 })] }).text()).toContain('500 o');
        expect(monter({ documents: [doc({ taille_octets: 3_500_000 })] }).text()).toContain('3.3 Mo');
    });

    it('n’offre le dépôt qu’avec document.deposer', () => {
        expect(monter({ peutDeposer: true }).find('input[type="file"]').exists()).toBe(true);
        expect(monter({ peutDeposer: false }).find('input[type="file"]').exists()).toBe(false);
    });

    it('n’offre le retrait qu’avec document.supprimer', () => {
        expect(monter({ peutSupprimer: true }).text()).toContain('Retirer');
        expect(monter({ peutSupprimer: false }).text()).not.toContain('Retirer');
    });

    it('affiche « Aucun document » sur une liste vide', () => {
        expect(monter({ documents: [] }).text()).toContain('Aucun document');
    });

    it('nomme le type, ou « Sans type » à défaut', () => {
        expect(monter().text()).toContain('Devis');
        expect(monter({ documents: [doc({ type: null })] }).text()).toContain('Sans type');
    });
});
