<template>
  <div class="mb-3">
    <label v-if="label" :for="id" class="form-label">
      {{ label }}
      <span v-if="required" class="text-danger">*</span>
    </label>
    <slot />
    <div v-if="hint && !error" class="form-text">{{ hint }}</div>
    <div v-if="error" class="invalid-feedback d-block">{{ error }}</div>
  </div>
</template>

<script setup>
defineProps({
  label: { type: String, default: null },
  id: { type: String, default: null },
  hint: { type: String, default: null },
  error: { type: String, default: null },
  required: { type: Boolean, default: false },
})
</script>
