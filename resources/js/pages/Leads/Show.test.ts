import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it } from 'vitest';
import { routerMock } from '@/test/preparer';
import Show from './Show.vue';

const stubs = { FriseActivites: true };

function lead(over: Record<string, unknown> = {}) {
    return {
        id: 5, numero: 'LEAD-2026-00005', raison_sociale: 'Cible SARL', ice: '001', site_web: null,
        statut: 'Nouveau', source: 'Salon', ville: null, proprietaire: 'Hicham', score: null, commentaire: null, ...over,
    };
}
const edition = {
    raison_sociale: 'Cible SARL', ice: '001', site_web: null, score: null,
    source_id: 1, statut_id: 1, effectif: null, ca_estime: null, adresse: null, commentaire: null,
};
const qualif = {
    nb_sites: null, nb_agences: null, logiciel_actuel: null, erp_actuel: null, version_actuelle: null,
    nb_utilisateurs: null, hebergement: null, prestataire_actuel: null, usage_sage: 'NeSaitPas',
    version_sage: null, nb_utilisateurs_sage: null, revendeur_actuel: null, contrat_sage: null,
};
function score(over: Record<string, unknown> = {}) {
    return {
        suggestion: 45, total: 100, total_atteignable: 100,
        criteres: [
            { code: 'BUDGET', libelle: 'Budget identifié', poids: 15, acquis: true, evaluable: true },
            { code: 'RDV', libelle: 'Rendez-vous effectué', poids: 15, acquis: false, evaluable: true },
        ], ...over,
    };
}
function monter(props: Record<string, unknown> = {}) {
    return mount(Show, {
        global: { stubs },
        props: {
            lead: lead(), palier: null, scoreSuggere: score(), contacts: [], qualification: null,
            converti: false, societeId: null, peutConvertir: true, peutModifier: true,
            edition, qualif, sources: [{ id: 1, libelle: 'Salon' }], statuts: [{ id: 1, libelle: 'Nouveau' }],
            activites: [], typesActivite: [], ...props,
        },
    });
}

beforeEach(() => {
    routerMock.post.mockClear();
    routerMock.put.mockClear();
});

describe('Fiche lead (§5 / §15)', () => {
    it('affiche « Non scoré » quand le score est nul (pas un zéro)', () => {
        expect(monter().text()).toContain('Non scoré');
    });

    it('un lead converti renvoie vers sa société et cache convertir/modifier', () => {
        const w = monter({ converti: true, societeId: 9, peutModifier: false });
        expect(w.text()).toContain('Ce lead est converti');
        expect(w.find('a[href="/societes/9"]').exists()).toBe(true);
        expect(w.text()).not.toContain('Convertir en société');
    });

    it('montre le score SUGGÉRÉ, son détail et le plafond atteignable', () => {
        const t = monter().text();
        expect(t).toContain('Score suggéré');
        expect(t).toContain('45');
        expect(t).toContain('Budget identifié');
        expect(t).toContain('acquis');
        expect(t).toContain('pas encore');
    });

    it('signale un plafond réduit quand des critères sont hors d’atteinte', () => {
        const t = monter({ scoreSuggere: score({ total_atteignable: 85 }) }).text();
        expect(t).toContain('plafond réduit');
    });

    it('« Appliquer le score suggéré » soumet un PUT vers le lead', async () => {
        const w = monter();
        await w.findAll('button').find((b) => b.text().includes('Appliquer'))!.trigger('click');
        expect(routerMock.put).toHaveBeenCalledWith('/leads/5', expect.anything());
    });

    it('« Qualifier » ouvre le formulaire ; le bloc Sage suit usage_sage', async () => {
        const w = monter();
        await w.findAll('button').find((b) => b.text() === 'Qualifier')!.trigger('click');
        // usage_sage = NeSaitPas -> pas de bloc Sage.
        expect(w.text()).not.toContain('Revendeur actuel');

        // Passer à « Oui » révèle le bloc Sage.
        const selectUsage = w.findAll('select').find((s) => s.text().includes('Ne sait pas'))!;
        await selectUsage.setValue('Oui');
        expect(w.text()).toContain('Revendeur actuel');
    });
});
