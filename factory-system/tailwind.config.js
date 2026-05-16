import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';
import rtl from 'tailwindcss-rtl';

export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './app/View/Components/**/*.php',
    ],
    theme: {
        extend: {
            fontFamily: {
                cairo: ['Cairo', 'sans-serif'],
                arabic: ['Noto Naskh Arabic', 'serif'],
            },
            colors: {
                brand: {
                    50: '#eff6ff',
                    100: '#dbeafe',
                    200: '#bfdbfe',
                    500: '#3b82f6',
                    600: '#2563eb',
                    700: '#1d4ed8',
                    900: '#1e3a8a',
                },
                ink: {
                    900: '#111827',
                    700: '#374151',
                    500: '#6b7280',
                },
            },
            spacing: {
                sidebar: '17rem',
            },
        },
    },
    plugins: [forms, typography, rtl],
};
