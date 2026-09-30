import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],
    darkMode: false,
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                mono: ['"IBM Plex Mono"', ...defaultTheme.fontFamily.mono],
                display: ['Fraunces', ...defaultTheme.fontFamily.serif],
            },
           colors: {
                canvas: '#EEF0F4',
                surface: '#FFFFFF',
                ink: '#0F172A',
                'ink-muted': '#64748B',
                border: '#E2E5EB',
                signal: {
                    DEFAULT: '#3730A3',
                    hover: '#2E2585',
                },
                status: {
                    paid: '#0E7C4A',
                    pending: '#B45309',
                    overdue: '#C0152F',
                },
            },
        },
    },

    plugins: [forms],
};