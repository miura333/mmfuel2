<script setup>
import { reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api } from '../api'
import AppHeader from './AppHeader.vue'
const route = useRoute()
const router = useRouter()
const saving = ref(false)
const error = ref('')
const form = reactive({ trip: '', fuel: '', price: '', carId: route.query.carId || localStorage.getItem('carId') })
async function save() {
  error.value = ''
  saving.value = true
  try {
    await api.addFuel({ carId: Number(form.carId), trip: Number(form.trip), fuel: Number(form.fuel), price: Number(form.price) })
    router.push('/')
  } catch (e) { error.value = e.message } finally { saving.value = false }
}
</script>

<template>
  <main class="appWrapper listPage">
    <AppHeader title="Add" back><button class="headerAction" :disabled="saving" @click="save">Save</button></AppHeader>
    <p v-if="error" class="message error">{{ error }}</p>
    <form @submit.prevent="save">
      <div class="historyCell"><input v-model="form.trip" required min="0" inputmode="numeric" type="number" class="addInputForm addInputFormText" placeholder="trip"></div>
      <div class="appHeaderBorder" />
      <div class="historyCell"><input v-model="form.fuel" required min="0.01" step="0.01" inputmode="decimal" type="number" class="addInputForm addInputFormText" placeholder="fuel"></div>
      <div class="appHeaderBorder" />
      <div class="historyCell"><input v-model="form.price" required min="0" inputmode="numeric" type="number" class="addInputForm addInputFormText" placeholder="price"></div>
    </form>
  </main>
</template>
