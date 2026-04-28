<template>
  <div :class="wrapperClass" aria-busy="true" aria-label="Loading…">
    <div v-for="i in count" :key="i" class="mb-3 placeholder-glow">
      <div class="placeholder rounded mb-1" :style="{ width: labelWidth(i), height: '0.85rem' }" />
      <div class="placeholder rounded w-100" style="height: 2.25rem" />
    </div>
    <div class="placeholder-glow">
      <div class="placeholder rounded" style="width: 6rem; height: 2.25rem" />
    </div>
  </div>
</template>

<script setup>
defineProps({
  count:        { type: Number, default: 3 },
  wrapperClass: { type: String, default: '' },
})

function labelWidth(i) {
  const widths = ['25%', '33%', '20%', '28%', '22%']
  return widths[(i - 1) % widths.length]
}
</script>
