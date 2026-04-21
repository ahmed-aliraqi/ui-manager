<template>
  <div>
    <!-- Multi-select -->
    <div v-if="field.multiple" class="space-y-1.5">
      <label
        v-for="opt in field.options"
        :key="opt.value"
        class="flex items-center gap-2 text-sm cursor-pointer"
      >
        <input
          type="checkbox"
          :value="opt.value"
          :checked="Array.isArray(modelValue) && modelValue.includes(opt.value)"
          @change="toggleMulti(opt.value, $event.target.checked)"
          class="rounded border-input"
        />
        {{ opt.label }}
      </label>
    </div>

    <!-- Single select with optional search -->
    <div v-else class="relative">
      <input
        v-if="field.searchable"
        v-model="search"
        type="text"
        placeholder="Search…"
        class="w-full h-9 rounded-md border border-input bg-background px-3 py-1 text-sm mb-1 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
      />
      <select
        :id="id"
        :value="modelValue"
        @change="$emit('update:modelValue', $event.target.value)"
        class="w-full h-9 rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
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
