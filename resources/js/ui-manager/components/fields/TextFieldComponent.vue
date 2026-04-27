<template>
  <div class="relative">
    <input
      :id="id"
      type="text"
      :value="modelValue ?? ''"
      @input="onInput"
      @focus="onFocus"
      @blur="onBlur"
      @keydown="onKeydown"
      :placeholder="field.props?.placeholder"
      :maxlength="field.max_length || undefined"
      class="w-full h-9 rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
    />
    <VariableAutocomplete
      ref="autocompleteRef"
      :value="modelValue"
      :active="isActive"
      @insert="$emit('update:modelValue', $event)"
    />
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import VariableAutocomplete from './VariableAutocomplete.vue'

const props = defineProps({ id: String, field: Object, modelValue: { default: null } })
const emit  = defineEmits(['update:modelValue'])

const isFocused      = ref(false)
const escapedClosed  = ref(false)
const autocompleteRef = ref(null)

const isActive = computed(() => isFocused.value && !escapedClosed.value)

function onFocus()  { isFocused.value = true; escapedClosed.value = false }
function onBlur()   { isFocused.value = false }

function onInput(e) {
  emit('update:modelValue', e.target.value)
  escapedClosed.value = false
}

function onKeydown(e) {
  if (e.key === 'Escape')    { e.preventDefault(); escapedClosed.value = true; return }
  if (!isActive.value) return
  if (e.key === 'ArrowDown') { e.preventDefault(); autocompleteRef.value?.navigate(1) }
  else if (e.key === 'ArrowUp')  { e.preventDefault(); autocompleteRef.value?.navigate(-1) }
  else if (e.key === 'Enter')    { if (autocompleteRef.value?.confirmSelection()) e.preventDefault() }
}
</script>
