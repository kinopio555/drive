// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  ssr: false,
  devtools: { enabled: false },
  css: [
    'vuetify/styles',
    '@mdi/font/css/materialdesignicons.min.css',
  ],
  components: true,
  build: {
    transpile: ['vuetify'],
  },
})
