import { createApp } from 'vue'
import { createPinia } from "pinia"
import App from './App.vue'
import router from './router'
import vuetify from './vuetify/vuetify'
import VueDatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css'
import VueQrcode from '@chenfengyuan/vue-qrcode';
import './styles.css'

createApp(App)
    .use(createPinia())
    .use(router)
    .use(vuetify)
    .component(VueQrcode.name, VueQrcode)
    .component('VueDatePicker', VueDatePicker)
    .mount('#app')

