<template>
  <div
    :class="[type === 'grow' ? 'spinner-grow' : 'spinner-border', `text-${variant}`, size && `spinner-${type === 'grow' ? 'grow' : 'border'}-${size}`]"
    role="status"
  >
    <span class="visually-hidden">{{ label }}</span>
  </div>
</template>

<script setup>
defineProps({
  variant: { type: String, default: 'primary' },
  type: { type: String, default: 'border' },  // 'border' | 'grow'
  size: { type: String, default: null },       // 'sm'
  label: { type: String, default: 'Loading...' },
})
</script>
