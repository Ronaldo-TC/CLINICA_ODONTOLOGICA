import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    resolve: { alias: { events: 'events/' } },
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'public/js/odontograma-3d.js', 'resources/js/radiologia-visor.js', 'resources/js/odontograma-admision.js'],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
    worker: { format: 'es' },
    optimizeDeps: { exclude: ['@cornerstonejs/dicom-image-loader'], include: ['dicom-parser'] },
});
