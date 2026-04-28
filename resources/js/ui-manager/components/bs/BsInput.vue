<template>
  <input
    :id="id"
    class="form-control"
    :class="[sizeClass, { 'is-invalid': error, 'is-valid': valid }]"
    :type="type"
    :value="modelValue"
    :placeholder="placeholder"
    :disabled="disabled"
    :readonly="readonly"
    :required="required"
    v-bind="$attrs"
    @input="$emit('update:modelValue', $event.target.value)"
  />
</template>

<script setup>
import { computed } from 'vue'

defineOptions({ inheritAttrs: false })

const props = defineProps({
  modelValue: { type: [String, Number], default: '' },
  type: { type: String, default: 'text' },
  id: { type: String, default: null },
  placeholder: { type: String, default: null },
  disabled: { type: Boolean, default: false },
  readonly: { type: Boolean, default: false },
  required: { type: Boolean, default: false },
  size: { type: String, default: null },  // 'sm' | 'lg'
  error: { type: String, default: null },
  valid: { type: Boolean, default: false },
})

defineEmits(['update:modelValue'])

const sizeClass = computed(() => props.size ? `form-control-${props.size}` : null)
</script>
