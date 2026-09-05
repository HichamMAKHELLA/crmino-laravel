import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import Dashboard from './Dashboard.vue';

function tuiles(over: Record<string, unknown> = {}) {
    return { mes_leads: 12, pipeline_pondere: 340000, nb_affaires_ouvertes: 3, mes_taches: 5, ca_gagne_mois: 120000, taux_gain: 33.3, ...over };
}
function monter(props: Record<string, unknown> = {}) {
    return mount(Dashboard, { props: { prenom: 'Hicham', tuiles: tuiles(), ...props } });
}

describe('Accueil commercial (§77)', () => {
    it('salue le commercial par son prénom', () => {
        expect(monter().text()).toContain('Bonjour Hicham');
    });

    it('affiche les tuiles chiffrées', () => {
        const t = monter().text();
        expect(t).toContain('12'); // mes leads
        expect(t).toContain('5');  // mes tâches
        expect(t).toContain('33.3 %'); // taux de gain
    });

    it('RG-IND-001 — sans affaire close, le taux n’existe pas (pas 0 %)', () => {
        const t = monter({ tuiles: tuiles({ taux_gain: null }) }).text();
        expect(t).toContain('Aucune affaire close sur la période');
        expect(t).not.toContain('0 %');
    });

    it('accorde « affaire ouverte » au pluriel', () => {
        expect(monter({ tuiles: tuiles({ nb_affaires_ouvertes: 1 }) }).text()).toContain('1 affaire ouverte');
        expect(monter({ tuiles: tuiles({ nb_affaires_ouvertes: 1 }) }).text()).not.toContain('1 affaires ouvertes');
        expect(monter({ tuiles: tuiles({ nb_affaires_ouvertes: 3 }) }).text()).toContain('3 affaires ouvertes');
    });
});
