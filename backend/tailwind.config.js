import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            // Override rounded-lg to match reference --radius: 0.7rem (Decision #5 in design.md).
            // Keeps markup clean with rounded-lg instead of arbitrary rounded-[0.7rem] values.
            // Non-lg radii (rounded-md on inputs) stay at Tailwind defaults.
            borderRadius: {
                lg: '0.7rem',
            },
        },
    },

    plugins: [forms],
};
