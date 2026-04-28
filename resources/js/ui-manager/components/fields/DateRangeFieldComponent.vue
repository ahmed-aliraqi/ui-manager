<template>
  <div class="d-flex align-items-end gap-2">
    <div class="flex-grow-1">
      <label class="form-label small text-muted mb-1">From</label>
      <input
        type="date"
        :value="start"
        :max="end || undefined"
        @change="onStartChange($event.target.value)"
        class="uim-form-control"
      />
    </div>

    <span class="mx-1 text-muted mb-2">&rarr;</span>

    <div class="flex-grow-1">
      <label class="form-label small text-muted mb-1">To</label>
      <input
        type="date"
        :value="end"
        :min="start || undefined"
        @change="onEndChange($event.target.value)"
        class="uim-form-control"
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
