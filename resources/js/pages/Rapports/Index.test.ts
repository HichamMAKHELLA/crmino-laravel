import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import Index from './Index.vue';

function monter(props: Record<string, unknown> = {}) {
    return mount(Index, {
        props: {
            onglet: 'previsionnel', du: null, au: null,
            previsionnel: null, motifs: null, entonnoir: null, ventilation: null, ...props,
        },
    });
}

describe('Rapports (§38/§39/§75/§76)', () => {
    it('affiche les quatre onglets', () => {
        const t = monter().text();
        expect(t).toContain('Prévisionnel');
        expect(t).toContain('Motifs de perte');
        expect(t).toContain('Entonnoir');
        expect(t).toContain('Ventilation');
    });

    it('§75 — l’onglet prévisionnel montre la probabilité effective', () => {
        const w = monter({
            onglet: 'previsionnel',
            previsionnel: [{ etape: 'Proposition', nb: 2, montant: 200000, pondere: 90000, probabilite_effective: 45 }],
        });
        expect(w.text()).toContain('Proposition');
        expect(w.text()).toContain('45 %');
    });

    it('§39 — l’entonnoir rend chaque palier', () => {
        const w = monter({
            onglet: 'entonnoir',
            entonnoir: [
                { palier: 'Leads affectés', nb: 4 },
                { palier: 'Contactés', nb: 2 },
            ],
        });
        expect(w.text()).toContain('Leads affectés');
        expect(w.text()).toContain('Contactés');
    });

    it('§76 — la ventilation montre le produit et la couverture', () => {
        const w = monter({
            onglet: 'ventilation',
            ventilation: {
                lignes: [{ produit: 'Sage Compta', gamme: 'Sage', famille: null, opportunites_ouvertes: 1, pipeline: 200000, gagnees: 1, ca_gagne: 400000 }],
                pipeline_total: 300000, pipeline_ventile: 200000, taux_pipeline: 66.7,
                ca_gagne_total: 600000, ca_gagne_ventile: 400000, taux_ca_gagne: 66.7,
                affaires_ouvertes_sans_ligne: 1,
            },
        });
        const t = w.text();
        expect(t).toContain('Sage Compta');
        expect(t).toContain('66.7 %');
        expect(t).toContain('sans ligne'); // affaires ouvertes sans ligne ventilable
    });

    it('§76 RG-IND-002 — un taux de couverture sans base s’affiche « — », jamais 0 %', () => {
        const w = monter({
            onglet: 'ventilation',
            ventilation: {
                lignes: [], pipeline_total: 0, pipeline_ventile: 0, taux_pipeline: null,
                ca_gagne_total: 0, ca_gagne_ventile: 0, taux_ca_gagne: null, affaires_ouvertes_sans_ligne: 0,
            },
        });
        expect(w.text()).toContain('—');
        expect(w.text()).not.toContain('0 %');
    });
});
