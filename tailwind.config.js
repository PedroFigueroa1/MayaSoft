import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/laravel/jetstream/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    darkMode: 'class', // Añade esto para soporte de modo oscuro

theme: {
    extend: {
        fontFamily: {
            sans: ['Figtree', ...defaultTheme.fontFamily.sans],
        },
        colors: {
            primary: {
                DEFAULT: '#152c6a', // azul principal, más oscuro
                dark: '#0f1e4d',    // azul noche
                medium: '#1e3a8a',  // azul medio (blue-800)
                light: '#3b82f6',   // azul brillante
            },
            secondary: '#1e40af',   // azul fuerte, para botones o bordes
            accent: {
                DEFAULT: '#2563eb', // azul vívido principal
                blue: '#3b82f6',    // azul brillante
                light: '#60a5fa'    // azul claro suave
            },
        },
        backgroundImage: {
            'top-gradient': 'linear-gradient(120deg, #0f1e4d 0%, #1e3a8a 100%)',
            'sidebar-gradient': 'linear-gradient(180deg, #0f1e4d 0%, #1e3a8a 100%)',
        }
    },
},


    plugins: [forms, typography],
};
