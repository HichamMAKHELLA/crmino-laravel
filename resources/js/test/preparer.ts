import { config } from '@vue/test-utils';
import { vi } from 'vitest';

/**
 * Préparation des tests de composant (§11). Inertia (`@inertiajs/vue3`) est
 * mocké globalement : les composants appellent `router.post/put/delete` et on
 * VÉRIFIE ces appels au lieu d'exercer une vraie navigation. `Link` devient une
 * simple ancre, et `usePage` rend un flash vide par défaut.
 */
export const routerMock = {
    post: vi.fn(),
    put: vi.fn(),
    delete: vi.fn(),
    get: vi.fn(),
    visit: vi.fn(),
};

vi.mock('@inertiajs/vue3', () => ({
    router: routerMock,
    usePage: () => ({ props: { flash: {} } }),
    useForm: (data: Record<string, unknown>) => ({
        ...data,
        errors: {},
        processing: false,
        post: vi.fn(),
        put: vi.fn(),
        delete: vi.fn(),
    }),
    Head: { name: 'Head', template: '<div><slot /></div>' },
    Link: { name: 'Link', props: ['href'], template: '<a :href="href"><slot /></a>' },
}));

// Un stub de <Link> global au cas où un composant l'utilise sans l'importer.
config.global.stubs = {};
