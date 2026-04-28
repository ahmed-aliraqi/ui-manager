<template>
  <input
    :id="id"
    type="time"
    :value="modelValue ?? ''"
    @change="$emit('update:modelValue', $event.target.value || null)"
    class="uim-form-control"
  />
</template>

<script setup>
defineProps({ id: String, field: Object, modelValue: { default: null } })
defineEmits(['update:modelValue'])
</script>
