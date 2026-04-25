<template>
  <div class="space-y-1.5">
    <!-- Label -->
    <div class="flex items-center justify-between">
      <label :for="fieldId" class="text-sm font-medium text-foreground">
        {{ field.label }}
        <span v-if="isRequired" class="text-destructive ml-0.5">*</span>
      </label>

      <!-- Variable copy: single format -->
      <button
        v-if="variableFormats.length === 1"
        type="button"
        @click="copyFormat(variableFormats[0])"
        class="text-xs text-muted-foreground hover:text-primary flex items-center gap-1 transition-colors"
        :title="`Copy: ${variableFormats[0]}`"
      >
        <CopyIcon class="w-3 h-3" />
        <code class="font-mono">{{ variableFormats[0] }}</code>
      </button>

      <!-- Variable copy: multiple formats — dropdown -->
      <div v-else-if="variableFormats.length > 1" class="relative" ref="dropdownRef">
        <button
          type="button"
          @click="showDropdown = !showDropdown"
          class="text-xs text-muted-foreground hover:text-primary flex items-center gap-1 transition-colors"
        >
          <CopyIcon class="w-3 h-3" />
          <span>Variables</span>
          <ChevronDownIcon class="w-3 h-3" />
        </button>
        <div
          v-if="showDropdown"
          class="absolute right-0 top-full mt-1 bg-popover border rounded-md shadow-lg z-50 py-1 min-w-max"
        >
          <button
            v-for="fmt in variableFormats"
            :key="fmt"
            type="button"
            @click="copyFormat(fmt); showDropdown = false"
            class="w-full px-3 py-1.5 text-left text-xs font-mono hover:bg-muted transition-colors"
          >
            {{ fmt }}
          </button>
        </div>
      </div>
    </div>

    <!-- Translatable field: render locale tabs + input per locale -->
    <div v-if="field.translatable" class="border rounded-md overflow-hidden">
      <!-- Locale tab bar -->
      <div class="flex border-b bg-muted/40">
        <button
          v-for="locale in locales"
          :key="locale"
          type="button"
          @click="activeLocale = locale"
          :class="[
            'px-3 py-1.5 text-xs font-medium uppercase tracking-wide transition-colors',
            activeLocale === locale
              ? 'bg-background text-foreground border-b-2 border-primary -mb-px'
              : 'text-muted-foreground hover:text-foreground',
          ]"
        >
          {{ locale }}
        </button>
      </div>
      <!-- Input for the active locale -->
      <div class="p-3">
        <component
          :is="fieldComponent"
          :id="fieldId + '-' + activeLocale"
          :field="field"
          :modelValue="localeValue"
          @update:modelValue="updateLocaleValue"
        />
      </div>
    </div>

    <!-- Non-translatable field -->
    <component
      v-else
      :is="fieldComponent"
      :id="fieldId"
      :field="field"
      :modelValue="modelValue"
      @update:modelValue="$emit('update:modelValue', $event)"
    />

    <!-- Help text -->
    <p v-if="field.help" class="text-xs text-muted-foreground">{{ field.help }}</p>

    <!-- Validation error -->
    <p v-if="error" role="alert" class="text-xs text-destructive flex items-center gap-1 mt-0.5">
      <AlertCircleIcon class="w-3 h-3 shrink-0" />
      {{ error }}
    </p>
  </div>
</template>

<script setup>
import { computed, inject, ref, onMounted, onUnmounted } from 'vue'
import { CopyIcon, AlertCircleIcon, ChevronDownIcon } from 'lucide-vue-next'
import { useLocales } from '../../composables/useConfig.js'
import TextFieldComponent from './TextFieldComponent.vue'
import TextareaFieldComponent from './TextareaFieldComponent.vue'
import EditorFieldComponent from './EditorFieldComponent.vue'
import SelectFieldComponent from './SelectFieldComponent.vue'
import ImageFieldComponent from './ImageFieldComponent.vue'
import FileFieldComponent from './FileFieldComponent.vue'
import ColorFieldComponent from './ColorFieldComponent.vue'
import DateFieldComponent from './DateFieldComponent.vue'
import TimeFieldComponent from './TimeFieldComponent.vue'
import DatetimeFieldComponent from './DatetimeFieldComponent.vue'
import DateRangeFieldComponent from './DateRangeFieldComponent.vue'
import UrlFieldComponent from './UrlFieldComponent.vue'
import PriceFieldComponent from './PriceFieldComponent.vue'

const props = defineProps({
  field:      Object,
  modelValue: { default: null },
  error:      { type: String, default: null },
})

const emit = defineEmits(['update:modelValue'])

const { locales, defaultLocale } = useLocales()
const activeLocale = ref(defaultLocale)

const sectionName = inject('sectionName', 'section')
const fieldId = computed(() => `field-${sectionName}-${props.field.name}`)
const isRequired = computed(() => props.field.rules?.includes('required'))

const variableFormats = computed(() => props.field.variable_formats ?? [])

// Dropdown state
const showDropdown = ref(false)
const dropdownRef = ref(null)

function handleOutsideClick(e) {
  if (dropdownRef.value && !dropdownRef.value.contains(e.target)) {
    showDropdown.value = false
  }
}

onMounted(() => document.addEventListener('click', handleOutsideClick, true))
onUnmounted(() => document.removeEventListener('click', handleOutsideClick, true))

// Current locale value from the locale-keyed object stored in modelValue.
const localeValue = computed(() => {
  const val = props.modelValue
  if (val && typeof val === 'object' && !Array.isArray(val)) {
    return val[activeLocale.value] ?? ''
  }
  return typeof val === 'string' ? val : ''
})

function updateLocaleValue(newVal) {
  const current = (props.modelValue && typeof props.modelValue === 'object' && !Array.isArray(props.modelValue))
    ? { ...props.modelValue }
    : {}
  emit('update:modelValue', { ...current, [activeLocale.value]: newVal })
}

const fieldComponent = computed(() => {
  switch (props.field.type) {
    case 'textarea':   return TextareaFieldComponent
    case 'editor':     return EditorFieldComponent
    case 'select':     return SelectFieldComponent
    case 'image':      return ImageFieldComponent
    case 'file':       return FileFieldComponent
    case 'color':      return ColorFieldComponent
    case 'date':       return DateFieldComponent
    case 'time':       return TimeFieldComponent
    case 'datetime':   return DatetimeFieldComponent
    case 'date_range': return DateRangeFieldComponent
    case 'url':        return UrlFieldComponent
    case 'price':      return PriceFieldComponent
    default:           return TextFieldComponent
  }
})

function copyFormat(fmt) {
  navigator.clipboard.writeText(fmt).catch(() => {})
}
</script>
