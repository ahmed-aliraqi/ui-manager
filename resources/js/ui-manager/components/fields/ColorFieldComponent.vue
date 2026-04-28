<template>
  <div class="d-flex align-items-center gap-2">
    <!-- Color swatch + native picker -->
    <div class="position-relative">
      <div
        :style="{ backgroundColor: displayValue || '#000000' }"
        class="border rounded cursor-pointer"
        style="width:2.5rem;height:2.5rem;"
        @click="pickerRef?.click()"
        :title="displayValue"
      />
      <input
        ref="pickerRef"
        :type="field.alpha ? 'color' : 'color'"
        :value="displayValue || '#000000'"
        @input="onPickerChange($event.target.value)"
        class="visually-hidden"
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
      class="uim-form-control uim-form-control-sm font-monospace"
      style="width:8rem"
    />

    <!-- Clear button -->
    <button
      v-if="modelValue"
      type="button"
      @click="$emit('update:modelValue', null)"
      class="btn btn-link btn-sm text-danger p-0"
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
