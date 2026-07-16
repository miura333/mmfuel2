<script setup>
import { onMounted, ref } from 'vue'
import { api } from '../api'
import AppHeader from './AppHeader.vue'

const histories = ref([])
const error = ref('')
onMounted(async () => {
  try { histories.value = (await api.dashboard(localStorage.getItem('carId'))).history }
  catch (e) { error.value = e.message }
})
</script>

<template>
  <main class="appWrapper listPage">
    <AppHeader title="History" back />
    <p v-if="error" class="message error">{{ error }}</p>
    <template v-for="history in histories" :key="history.id">
      <div class="historyCell">
        <div class="historyCellColl1"><span>{{ history.date }}</span><span>{{ history.fuel_rate }} km/l</span></div>
        <div class="historyCellColl2"><span>{{ history.trip }} km</span><span>{{ history.fuel }} l</span><span>{{ history.price_of_fuel }} yen</span></div>
      </div>
      <div class="appHeaderBorder" />
    </template>
  </main>
</template>
