<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { api } from '../api'
import AppHeader from './AppHeader.vue'
const router = useRouter()
const carName = ref('')
const saving = ref(false)
const error = ref('')
async function save() {
  saving.value = true
  error.value = ''
  try {
    const result = await api.addCar(carName.value)
    localStorage.setItem('carId', String(result.id))
    router.push('/')
  } catch (e) { error.value = e.message } finally { saving.value = false }
}
</script>

<template>
  <main class="appWrapper listPage">
    <AppHeader title="Add" back><button class="headerAction" :disabled="saving" @click="save">Save</button></AppHeader>
    <p v-if="error" class="message error">{{ error }}</p>
    <form @submit.prevent="save">
      <div class="historyCell"><input v-model="carName" required maxlength="255" class="addInputForm addInputFormText" placeholder="Your car name here"></div>
      <div class="appHeaderBorder" />
    </form>
  </main>
</template>
