<template>
  <select
    :id="id"
    class="form-select"
    :class="[size && `form-select-${size}`, { 'is-invalid': error, 'is-valid': valid }]"
    :value="modelValue"
    :disabled="disabled"
    :required="required"
    v-bind="$attrs"
    @change="$emit('update:modelValue', $event.target.value)"
  >
    <option v-if="placeholder" value="" disabled :selected="!modelValue">{{ placeholder }}</option>
    <option
      v-for="opt in normalizedOptions"
      :key="opt.value"
      :value="opt.value"
      :disabled="opt.disabled"
    >
      {{ opt.label }}
    </option>
  </select>
</template>

<script setup>
import { computed } from 'vue'

defineOptions({ inheritAttrs: false })

const props = defineProps({
  modelValue: { type: [String, Number], default: '' },
  options: { type: Array, default: () => [] },  // [{value, label, disabled?}] or ['string', ...]
  id: { type: String, default: null },
  placeholder: { type: String, default: null },
  disabled: { type: Boolean, default: false },
  required: { type: Boolean, default: false },
  size: { type: String, default: null },
  error: { type: String, default: null },
  valid: { type: Boolean, default: false },
})

defineEmits(['update:modelValue'])

const normalizedOptions = computed(() =>
  props.options.map((o) =>
    typeof o === 'object' ? o : { value: o, label: o }
  )
)
</script>
