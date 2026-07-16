<script setup>
defineProps({ title: { type: String, required: true }, back: { type: Boolean, default: false } })
</script>

<template>
  <header class="appHeader">
    <div class="appHeaderTitle">
      <div class="headerSide">
        <RouterLink v-if="back" class="linkBackButtonText" to="/">Back</RouterLink>
        <slot name="left" />
      </div>
      <div>{{ title }}</div>
      <div class="headerSide headerRight"><slot /></div>
    </div>
  </header>
  <div class="appHeaderBorder" />
</template>
