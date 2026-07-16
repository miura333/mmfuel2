<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { api } from '../api'
import AppHeader from './AppHeader.vue'

const router = useRouter()
const data = ref(null)
const error = ref('')

onMounted(async () => {
  try {
    const carId = localStorage.getItem('carId')
    data.value = await api.dashboard(carId)
  } catch (e) {
    if (/車両が登録/.test(e.message)) return router.replace('/cars/add')
    error.value = e.message
  }
})
</script>

<template>
  <main class="appWrapper">
    <AppHeader :title="data?.carName || 'mmfuel'">
      <RouterLink class="linkBackButtonText" to="/cars">List</RouterLink>
    </AppHeader>
    <p v-if="error" class="message error">{{ error }}</p>
    <div class="latestTitle latestAndAverageTitleText">Latest</div>
    <div class="latestValue">
      <span v-if="!data" class="loading">Loading…</span>
      <template v-else><em class="fuelValueText">{{ data.latestRate }}</em><em class="fuelUnitText">km/l</em></template>
    </div>
    <div class="averageTitle latestAndAverageTitleText">Average</div>
    <div class="averageValue">
      <span v-if="!data" class="loading">Loading…</span>
      <template v-else><em class="fuelValueText">{{ data.averageRate }}</em><em class="fuelUnitText">km/l</em></template>
    </div>
    <div class="addButtonParent"><RouterLink class="addHistoryButton linkButtonText" :to="`/fuel/add?carId=${data?.carId || ''}`">add</RouterLink></div>
    <div class="historyButtonParent"><RouterLink class="addHistoryButton linkButtonText" to="/history">history</RouterLink></div>
  </main>
</template>
