import { config } from '@vue/test-utils';
import { vi } from 'vitest';
import { reactive } from 'vue';

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
    // useForm partage les mêmes spies que router : on vérifie l'URL soumise.
    // reactive() pour que v-model déclenche les `computed` qui en dépendent.
    useForm: (data: Record<string, unknown>) => reactive({
        ...data,
        errors: {},
        processing: false,
        post: routerMock.post,
        put: routerMock.put,
        delete: routerMock.delete,
    }),
    Head: { name: 'Head', template: '<div><slot /></div>' },
    Link: { name: 'Link', props: ['href'], template: '<a :href="href"><slot /></a>' },
}));

// Un stub de <Link> global au cas où un composant l'utilise sans l'importer.
config.global.stubs = {};
