module.exports = {
  purge: {
    enabled: process.env.NODE_ENV === 'production',
    mode: 'layers',
    layers: ['base', 'components', 'utilities'],
    content: [
      './src/**/*.vue',
    ],
  },
  darkMode: 'media', // or 'media' or 'class'
  theme: {
    container: {
      center: true,
      padding: {
        default: '1rem',
        sm: '2rem',
        lg: '3rem',
        xl: '4rem',
      },
    },
    extend: {},
  },
  variants: {
    extend: {},
  },
  plugins: [
    require('@tailwindcss/ui'),
    require('@tailwindcss/custom-forms'),
  ],
}
