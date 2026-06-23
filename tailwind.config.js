/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './public/**/*.php',
    './includes/**/*.php',
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: ['"Segoe UI"', 'Calibri', 'Arial', 'sans-serif'],
      },
      colors: {
        brand: {
          DEFAULT: '#91191A',
          gold: '#caa14a',
          dark: '#6f1213',
        },
        basbg: '#F5F5EF',
        tablehead: '#d7b777',
      },
      boxShadow: {
        card: '0 8px 24px rgba(0, 0, 0, 0.06)',
      },
    },
  },
  plugins: [],
};
