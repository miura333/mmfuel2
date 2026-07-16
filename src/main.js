import { createApp } from 'vue'
import { createRouter, createWebHashHistory } from 'vue-router'
import App from './App.vue'
import DashboardView from './components/DashboardView.vue'
import HistoryView from './components/HistoryView.vue'
import CarListView from './components/CarListView.vue'
import AddFuelView from './components/AddFuelView.vue'
import AddCarView from './components/AddCarView.vue'
import './style.css'

const router = createRouter({
  history: createWebHashHistory(),
  routes: [
    { path: '/', name: 'dashboard', component: DashboardView, meta: { transition: 'back' } },
    { path: '/history', name: 'history', component: HistoryView },
    { path: '/cars', name: 'cars', component: CarListView },
    { path: '/fuel/add', name: 'add-fuel', component: AddFuelView },
    { path: '/cars/add', name: 'add-car', component: AddCarView },
  ],
})

const app = createApp(App)
app.use(router)

router.isReady().then(() => {
  app.mount('#app')
})

if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => navigator.serviceWorker.register('./sw.js'))
}
