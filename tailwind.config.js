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
                    50: '#F0F7F1',
                    100: '#DCEBDD',
                    200: '#B9D7BC',
                    300: '#8FBC94',
                    400: '#5F9A67',
                    500: '#3D7A45',
                    600: '#2C6334',
                    700: '#165823',
                    800: '#12471C',
                    900: '#0D3514',
                },
                cream: {
                    DEFAULT: '#FFF2F2',
                    dark: '#FFDADA',
                },
                brown: {
                    50: '#FFF2F2',
                    100: '#FFDADA',
                    200: '#FFC3C9',
                    300: '#FFA3AD',
                    400: '#FF788D',
                    500: '#B93A50',
                    600: '#97293D',
                    700: '#7A2131',
                    800: '#5C1826',
                    900: '#41101A',
                },
                accent: {
                    50: '#FFF2F2',
                    100: '#FFE4E7',
                    200: '#FFDADA',
                    300: '#FFB3BE',
                    400: '#FF95A5',
                    500: '#FF788D',
                    600: '#F05A72',
                    700: '#D13F58',
                    800: '#A82F44',
                    900: '#7A2131',
                },
            },
        },
    },

    plugins: [forms],
};
