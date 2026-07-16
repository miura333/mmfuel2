<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { api } from '../api'
import AppHeader from './AppHeader.vue'
const router = useRouter()
const cars = ref([])
const error = ref('')
onMounted(async () => {
  try { cars.value = (await api.cars()).cars } catch (e) { error.value = e.message }
})
function select(id) {
  localStorage.setItem('carId', String(id))
  router.push('/')
}
</script>

<template>
  <main class="appWrapper listPage">
    <AppHeader title="List" back><RouterLink class="linkBackButtonText" to="/cars/add">Add</RouterLink></AppHeader>
    <p v-if="error" class="message error">{{ error }}</p>
    <template v-for="car in cars" :key="car.id">
      <button class="historyCell carlistCellColl" @click="select(car.id)">{{ car.car_name }}</button>
      <div class="appHeaderBorder" />
    </template>
  </main>
</template>
