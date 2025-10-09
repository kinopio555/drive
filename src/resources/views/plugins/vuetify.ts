import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import { defineNuxtPlugin } from 'nuxt/app'

export default defineNuxtPlugin((nuxtApp:any) => {
  const vuetify = createVuetify({ components })
  nuxtApp.vueApp.use(vuetify)
})
