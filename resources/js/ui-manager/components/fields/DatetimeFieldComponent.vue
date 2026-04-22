<template>
  <input
    :id="id"
    type="datetime-local"
    :value="modelValue ?? ''"
    :min="field.min || undefined"
    :max="field.max || undefined"
    @change="$emit('update:modelValue', $event.target.value || null)"
    class="h-9 rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
  />
</template>

<script setup>
defineProps({ id: String, field: Object, modelValue: { default: null } })
defineEmits(['update:modelValue'])
</script>
