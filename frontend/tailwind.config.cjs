module.exports = {
  content: [
    './index.html',
    './src/**/*.{js,jsx,ts,tsx,html}'
  ],
  theme: {
    extend: {
      screens: {
        'bp986': '986px'
      },
      colors: {
        'revio-navy': '#061826',
        'revio-panel': '#0B2235',
        'revio-card': '#0F2B3F',
        'revio-accent': '#16C1E0',
        'revio-muted': '#8CA3B8'
      }
    }
  },
  plugins: []
}
