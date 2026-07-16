<template>
  <RouterView v-slot="{ Component, route }">
    <Transition :name="route.meta.transition || 'forward'" mode="out-in">
      <component :is="Component" :key="route.fullPath" />
    </Transition>
  </RouterView>
</template>
