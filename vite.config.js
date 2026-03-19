import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import path from 'path'

export default defineConfig({
    plugins: [
        vue(),
        laravel({
            input: [
                'resources/sass/app.scss',
                'resources/js/app.js',
                'resources/css/dark-mode.css',
                'resources/js/onboarding-tour.js',
                'resources/js/subscription-countdown.js',
                'resources/js/dark-mode.js',
                'resources/js/notifications-poll.js',
                'resources/js/support-chat.js',
                'resources/js/ai-chat.js',
            ],
            refresh: true,
        }),
    ],
    resolve: {
        alias: {
            'vue': 'vue/dist/vue.esm-bundler.js',
        }
    },
});
