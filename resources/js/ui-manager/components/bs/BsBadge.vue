<template>
  <span
    class="badge"
    :class="[`text-bg-${variant}`, { 'rounded-pill': pill }]"
  >
    <slot />
  </span>
</template>

<script setup>
defineProps({
  variant: { type: String, default: 'primary' },
  pill: { type: Boolean, default: false },
})
</script>
