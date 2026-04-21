import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import { router } from './router/index.js'
import './assets/app.css'

const app = createApp(App)

app.use(createPinia())
app.use(router)

app.mount('#ui-manager-app')
