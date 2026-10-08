import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: {
                    50: '#F4F7EC',
                    100: '#E7EDD9',
                    200: '#CBD9B0',
                    300: '#91AC67',
                    400: '#7A9449',
                    500: '#597928',
                    600: '#4C6822',
                    700: '#3E541C',
                    800: '#334517',
                    900: '#2A3A13',
                },
                cream: {
                    DEFAULT: '#FCECD8',
                    dark: '#F6DEC2',
                },
                brown: {
                    50: '#F9F1EA',
                    100: '#F0E0D2',
                    200: '#E2C4AC',
                    300: '#C79A78',
                    400: '#A5643B',
                    500: '#6E3511',
                    600: '#5D2D0E',
                    700: '#4C240B',
                    800: '#3B1C09',
                    900: '#2C1406',
                },
            },
        },
    },

    plugins: [forms],
};
