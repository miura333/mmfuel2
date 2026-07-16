<template>
  <RouterView v-slot="{ Component, route }">
    <Transition :name="route.meta.transition || 'forward'">
      <component :is="Component" :key="route.fullPath" />
    </Transition>
  </RouterView>
</template>
