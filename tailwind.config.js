/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ['./app/Views/**/*.php'],
  darkMode: 'selector',
  theme: {
    extend: {
      fontFamily: {
        sans: ['Inter', 'system-ui', '-apple-system', 'sans-serif'],
      },
      colors: {
        primary: {
          50: '#EFF6FF',
          100: '#DBEAFE',
          200: '#BFDBFE',
          300: '#93C5FD',
          400: '#60A5FA',
          500: '#3B82F6',
          600: '#1E5FBE',
          700: '#1A4FA0',
          800: '#153F80',
          900: '#0F2E5F',
          950: '#0A1F42',
        },
        /* Aksen kedua (emas) — dipakai tipis-tipis (garis aksen, highlight) supaya palet tidak
           monokrom biru+putih; selaras dengan lambang instansi pemerintah yang biasa memadukan
           navy dengan emas. */
        gold: {
          50: '#FFFBEB',
          100: '#FEF3C7',
          200: '#FDE68A',
          300: '#FCD34D',
          400: '#FBBF24',
          500: '#D97706',
          600: '#B45309',
        },
      },
    },
  },
  plugins: [],
};
