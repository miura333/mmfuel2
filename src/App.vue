<script setup>
import { ref, watch } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()
const transitionName = ref('')
let initialRoute = true

watch(
  () => route.fullPath,
  () => {
    if (initialRoute) {
      initialRoute = false
      transitionName.value = ''
      return
    }
    transitionName.value = route.meta.transition || 'forward'
  },
  { immediate: true, flush: 'sync' },
)
</script>

<template>
  <RouterView v-slot="{ Component, route }">
    <Transition
      :name="transitionName"
      :css="transitionName !== ''"
    >
      <component :is="Component" :key="route.fullPath" />
    </Transition>
  </RouterView>
</template>
