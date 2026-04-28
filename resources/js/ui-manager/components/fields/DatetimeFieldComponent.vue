<template>
  <input
    :id="id"
    type="datetime-local"
    :value="modelValue ?? ''"
    :min="field.min || undefined"
    :max="field.max || undefined"
    @change="$emit('update:modelValue', $event.target.value || null)"
    class="uim-form-control"
  />
</template>

<script setup>
defineProps({ id: String, field: Object, modelValue: { default: null } })
defineEmits(['update:modelValue'])
</script>
