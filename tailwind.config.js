/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ['./*.html', './assets/js/**/*.js'],
  theme: {
    extend: {
      colors: {
        'brand-blue': '#0274BD',
        'brand-red': '#EC1E1B',
        'brand-yellow': '#FFF017',
        'brand-navy': '#081D36',
      },
    },
  },
  plugins: [],
};
