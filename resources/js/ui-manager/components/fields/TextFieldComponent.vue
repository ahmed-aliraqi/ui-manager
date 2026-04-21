<template>
  <div class="relative">
    <input
      :id="id"
      type="text"
      :value="modelValue ?? ''"
      @input="$emit('update:modelValue', $event.target.value)"
      :placeholder="field.props?.placeholder"
      :maxlength="field.max_length || undefined"
      class="w-full h-9 rounded-md border border-input bg-background px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
    />
    <!-- Variable autocomplete hint -->
    <VariableAutocomplete
      v-if="showAutocomplete"
      :value="modelValue"
      @insert="$emit('update:modelValue', $event)"
    />
  </div>
</template>

<script setup>
import { computed } from 'vue'
import VariableAutocomplete from './VariableAutocomplete.vue'

const props = defineProps({ id: String, field: Object, modelValue: { default: null } })
defineEmits(['update:modelValue'])

const showAutocomplete = computed(() =>
  typeof props.modelValue === 'string' && props.modelValue.includes('%')
)
</script>
