<template>
  <div class="space-y-1">
    <div class="relative">
      <span class="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground pointer-events-none">
        <LinkIcon class="w-3.5 h-3.5" />
      </span>
      <input
        :id="id"
        type="url"
        :value="modelValue ?? ''"
        @input="onInput"
        @focus="onFocus"
        @blur="onBlur"
        @keydown="onKeydown"
        placeholder="https://example.com"
        class="w-full h-9 rounded-md border bg-background pl-8 pr-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
        :class="urlError ? 'border-destructive focus-visible:ring-destructive' : 'border-input'"
      />
      <VariableAutocomplete
        ref="autocompleteRef"
        :value="modelValue"
        :active="isActive"
        @insert="$emit('update:modelValue', $event)"
      />
    </div>
    <p v-if="urlError" class="text-xs text-destructive">{{ urlError }}</p>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { LinkIcon } from 'lucide-vue-next'
import VariableAutocomplete from './VariableAutocomplete.vue'

const props = defineProps({ id: String, field: Object, modelValue: { default: null } })
const emit  = defineEmits(['update:modelValue'])

const isFocused      = ref(false)
const escapedClosed  = ref(false)
const autocompleteRef = ref(null)
const urlError        = ref(null)

const isActive = computed(() => isFocused.value && !escapedClosed.value)

function onFocus()  { isFocused.value = true; escapedClosed.value = false }

function onBlur() {
  isFocused.value = false
  validate()
}

function onInput(e) {
  urlError.value = null
  emit('update:modelValue', e.target.value || null)
  escapedClosed.value = false
}

function onKeydown(e) {
  if (e.key === 'Escape')    { e.preventDefault(); escapedClosed.value = true; return }
  if (!isActive.value) return
  if (e.key === 'ArrowDown') { e.preventDefault(); autocompleteRef.value?.navigate(1) }
  else if (e.key === 'ArrowUp')  { e.preventDefault(); autocompleteRef.value?.navigate(-1) }
  else if (e.key === 'Enter')    { if (autocompleteRef.value?.confirmSelection()) e.preventDefault() }
}

function validate() {
  const val = props.modelValue
  if (!val || val.includes('%')) return
  try {
    new URL(val)
    urlError.value = null
  } catch {
    urlError.value = 'Please enter a valid URL (e.g. https://example.com)'
  }
}
</script>
