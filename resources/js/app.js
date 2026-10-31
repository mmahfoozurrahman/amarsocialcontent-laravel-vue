import { createApp } from 'vue';
import App from './App.vue';
import router from './router';

// Import Bootstrap 5 JavaScript bundle
import 'bootstrap/dist/js/bootstrap.bundle.min.js';

const app = createApp(App);

app.use(router);
app.mount('#app');
