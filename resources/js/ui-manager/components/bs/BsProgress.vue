<template>
  <div class="progress" :style="{ height: height }">
    <div
      class="progress-bar"
      :class="[variant && `bg-${variant}`, { 'progress-bar-striped': striped, 'progress-bar-animated': animated }]"
      role="progressbar"
      :style="{ width: `${value}%` }"
      :aria-valuenow="value"
      aria-valuemin="0"
      aria-valuemax="100"
    >
      <slot>{{ showLabel ? `${value}%` : '' }}</slot>
    </div>
  </div>
</template>

<script setup>
defineProps({
  value: { type: Number, default: 0 },
  variant: { type: String, default: null },
  striped: { type: Boolean, default: false },
  animated: { type: Boolean, default: false },
  showLabel: { type: Boolean, default: false },
  height: { type: String, default: null },
})
</script>
