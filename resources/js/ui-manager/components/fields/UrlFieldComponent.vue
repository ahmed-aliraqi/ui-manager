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
        @input="onInput($event.target.value)"
        @blur="validate"
        placeholder="https://example.com"
        class="w-full h-9 rounded-md border bg-background pl-8 pr-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
        :class="error ? 'border-destructive focus-visible:ring-destructive' : 'border-input'"
      />
    </div>
    <p v-if="error" class="text-xs text-destructive">{{ error }}</p>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { LinkIcon } from 'lucide-vue-next'

const props = defineProps({ id: String, field: Object, modelValue: { default: null } })
const emit  = defineEmits(['update:modelValue'])

const error = ref(null)

function onInput(value) {
  error.value = null
  emit('update:modelValue', value || null)
}

function validate() {
  const val = props.modelValue
  if (!val) return

  try {
    new URL(val)
    error.value = null
  } catch {
    error.value = 'Please enter a valid URL (e.g. https://example.com)'
  }
}
</script>
