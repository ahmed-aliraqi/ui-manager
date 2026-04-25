<template>
  <div :class="['space-y-6', wrapperClass]" aria-busy="true" aria-label="Loading…">
    <div v-for="i in count" :key="i" class="space-y-1.5">
      <div class="h-4 rounded bg-muted animate-pulse" :style="{ width: labelWidth(i) }" />
      <div class="h-9 w-full rounded bg-muted animate-pulse" />
    </div>
    <div class="flex gap-3 pt-2 border-t">
      <div class="h-9 w-24 rounded bg-muted animate-pulse" />
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
