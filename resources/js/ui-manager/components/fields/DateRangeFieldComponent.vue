<template>
  <div class="flex items-center gap-2">
    <div class="flex-1">
      <label class="text-xs text-muted-foreground mb-1 block">From</label>
      <input
        type="date"
        :value="start"
        :max="end || undefined"
        @change="onStartChange($event.target.value)"
        class="w-full h-9 rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
      />
    </div>

    <span class="text-muted-foreground mt-5 shrink-0">→</span>

    <div class="flex-1">
      <label class="text-xs text-muted-foreground mb-1 block">To</label>
      <input
        type="date"
        :value="end"
        :min="start || undefined"
        @change="onEndChange($event.target.value)"
        class="w-full h-9 rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
      />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({ id: String, field: Object, modelValue: { default: null } })
const emit  = defineEmits(['update:modelValue'])

const start = computed(() => props.modelValue?.start ?? '')
const end   = computed(() => props.modelValue?.end   ?? '')

function onStartChange(value) {
  emit('update:modelValue', { start: value || null, end: end.value || null })
}

function onEndChange(value) {
  emit('update:modelValue', { start: start.value || null, end: value || null })
}
</script>
