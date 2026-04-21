<template>
  <div class="space-y-1.5">
    <!-- Label -->
    <div class="flex items-center justify-between">
      <label :for="fieldId" class="text-sm font-medium text-foreground">
        {{ field.label }}
        <span v-if="isRequired" class="text-destructive ml-0.5">*</span>
      </label>

      <!-- Variable copy button -->
      <button
        type="button"
        @click="copyVariable"
        class="text-xs text-muted-foreground hover:text-primary flex items-center gap-1 transition-colors"
        :title="`Copy variable: %${sectionName}.${field.name}%`"
      >
        <CopyIcon class="w-3 h-3" />
        <code class="font-mono">%{{ sectionName }}.{{ field.name }}%</code>
      </button>
    </div>

    <!-- Field component -->
    <component
      :is="fieldComponent"
      :id="fieldId"
      :field="field"
      :modelValue="modelValue"
      @update:modelValue="$emit('update:modelValue', $event)"
    />

    <!-- Help text -->
    <p v-if="field.help" class="text-xs text-muted-foreground">{{ field.help }}</p>
  </div>
</template>

<script setup>
import { computed, inject } from 'vue'
import { CopyIcon } from 'lucide-vue-next'
import TextFieldComponent from './TextFieldComponent.vue'
import TextareaFieldComponent from './TextareaFieldComponent.vue'
import EditorFieldComponent from './EditorFieldComponent.vue'
import SelectFieldComponent from './SelectFieldComponent.vue'
import ImageFieldComponent from './ImageFieldComponent.vue'
import FileFieldComponent from './FileFieldComponent.vue'

const props = defineProps({
  field: Object,
  modelValue: { default: null },
})

defineEmits(['update:modelValue'])

const sectionName = inject('sectionName', 'section')
const fieldId = computed(() => `field-${sectionName}-${props.field.name}`)
const isRequired = computed(() => props.field.rules?.includes('required'))

const fieldComponent = computed(() => {
  switch (props.field.type) {
    case 'textarea': return TextareaFieldComponent
    case 'editor':   return EditorFieldComponent
    case 'select':   return SelectFieldComponent
    case 'image':    return ImageFieldComponent
    case 'file':     return FileFieldComponent
    default:         return TextFieldComponent
  }
})

function copyVariable() {
  const variable = `%${sectionName}.${props.field.name}%`
  navigator.clipboard.writeText(variable).catch(() => {})
}
</script>
