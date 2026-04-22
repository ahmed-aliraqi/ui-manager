<template>
  <div class="flex items-center gap-3">
    <!-- Color swatch + native picker -->
    <div class="relative">
      <div
        :style="{ backgroundColor: displayValue || '#000000' }"
        class="w-10 h-10 rounded-md border border-input cursor-pointer shadow-sm ring-offset-background hover:opacity-90 transition-opacity"
        @click="pickerRef?.click()"
        :title="displayValue"
      />
      <input
        ref="pickerRef"
        :type="field.alpha ? 'color' : 'color'"
        :value="displayValue || '#000000'"
        @input="onPickerChange($event.target.value)"
        class="sr-only"
        aria-hidden="true"
      />
    </div>

    <!-- Hex text input -->
    <input
      :id="id"
      type="text"
      :value="displayValue ?? ''"
      @input="onTextChange($event.target.value)"
      @blur="normalizeHex"
      placeholder="#000000"
      maxlength="7"
      class="w-28 h-9 rounded-md border border-input bg-background px-3 py-1 text-sm font-mono shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
    />

    <!-- Clear button -->
    <button
      v-if="modelValue"
      type="button"
      @click="$emit('update:modelValue', null)"
      class="text-muted-foreground hover:text-destructive transition-colors text-xs"
    >
      Clear
    </button>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({ id: String, field: Object, modelValue: { default: null } })
const emit = defineEmits(['update:modelValue'])

const pickerRef = ref(null)

const displayValue = computed(() =>
  typeof props.modelValue === 'string' ? props.modelValue : null
)

function onPickerChange(hex) {
  emit('update:modelValue', hex.toUpperCase())
}

function onTextChange(value) {
  // Accept partial typing without forcing normalisation mid-edit
  emit('update:modelValue', value || null)
}

function normalizeHex() {
  const val = props.modelValue
  if (!val || typeof val !== 'string') return

  const trimmed = val.trim()
  // If it looks like a valid hex without the #, add it
  if (/^[0-9a-fA-F]{6}$/.test(trimmed)) {
    emit('update:modelValue', '#' + trimmed.toUpperCase())
  } else if (/^#[0-9a-fA-F]{6}$/.test(trimmed)) {
    emit('update:modelValue', trimmed.toUpperCase())
  }
}
</script>
