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
            // catalog.* tokens: scoped emerald palette for admin dashboard + catalog screens.
            // Scoped under catalog.* prevents leaking into POS, auth, and out-of-scope admin
            // screens (Decision #1 in design.md). The global primary swap will happen when the
            // sidebar workstream (admin-sidebar-refresh) lands.
            // Values extracted from reference repo ondtcx/point-of-sale-interface app/globals.css.
            colors: {
                catalog: {
                    primary: 'oklch(0.56 0.12 151)',  // Brand emerald — primary actions, links
                    accent: 'oklch(0.94 0.03 150)',   // Hover/accent background
                    muted: 'oklch(0.55 0.012 80)',    // Muted foreground text
                    border: 'oklch(0.9 0.008 90)',    // Subtle border color
                    card: 'oklch(1 0 0)',              // Card surface (white)
                },
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
