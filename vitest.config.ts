import vue from '@vitejs/plugin-vue';
import { fileURLToPath } from 'node:url';
import { defineConfig } from 'vitest/config';

/**
 * Config Vitest SÉPARÉE de vite.config.ts (§11). Les greffons Laravel /
 * Inertia / Wayfinder / Tailwind ne servent à rien sous jsdom et ralentiraient
 * — on ne garde que le plugin Vue et l'alias `@`. Les tests co-localisés dans
 * resources/js sont éprouvés au SEAM du composant : props en entrée, rendu et
 * appels Inertia en sortie (mockés dans le setup).
 */
export default defineConfig({
    plugins: [vue()],
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
    },
    test: {
        environment: 'jsdom',
        globals: true,
        setupFiles: ['./resources/js/test/preparer.ts'],
        include: ['resources/js/**/*.{test,spec}.ts'],
    },
});
