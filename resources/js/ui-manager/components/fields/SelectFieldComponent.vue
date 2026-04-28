<template>
  <div>
    <!-- Multi-select -->
    <div v-if="field.multiple">
      <div
        v-for="opt in field.options"
        :key="opt.value"
        class="uim-form-check uim-form-check-inline"
      >
        <input
          type="checkbox"
          :id="`${id}-${opt.value}`"
          :value="opt.value"
          :checked="Array.isArray(modelValue) && modelValue.includes(opt.value)"
          @change="toggleMulti(opt.value, $event.target.checked)"
          class="uim-form-check-input"
        />
        <label :for="`${id}-${opt.value}`" class="uim-form-check-label">{{ opt.label }}</label>
      </div>
    </div>

    <!-- Single select with optional search -->
    <div v-else>
      <input
        v-if="field.searchable"
        v-model="search"
        type="text"
        placeholder="Search…"
        class="uim-form-control uim-form-control-sm" style="margin-bottom:.5rem"
      />
      <select
        :id="id"
        :value="modelValue"
        @change="$emit('update:modelValue', $event.target.value)"
        class="uim-form-select"
      >
        <option value="">— Select —</option>
        <option
          v-for="opt in filteredOptions"
          :key="opt.value"
          :value="opt.value"
        >{{ opt.label }}</option>
      </select>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

const props = defineProps({ id: String, field: Object, modelValue: { default: null } })
const emit = defineEmits(['update:modelValue'])

const search = ref('')

const filteredOptions = computed(() => {
  if (!search.value) return props.field.options || []
  return (props.field.options || []).filter(o =>
    o.label.toLowerCase().includes(search.value.toLowerCase())
  )
})

function toggleMulti(value, checked) {
  const current = Array.isArray(props.modelValue) ? [...props.modelValue] : []
  if (checked) {
    emit('update:modelValue', [...current, value])
  } else {
    emit('update:modelValue', current.filter(v => v !== value))
  }
}
</script>
