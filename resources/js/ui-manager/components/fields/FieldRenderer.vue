<template>
  <div class="mb-3">
    <!-- Label row -->
    <div class="d-flex align-items-center justify-content-between mb-1">
      <label :for="fieldId" class="fw-medium mb-0" style="font-size:inherit">
        {{ field.label }}
        <span v-if="isRequired" class="text-danger ms-1">*</span>
      </label>

      <!-- Variable copy: single format -->
      <button
        v-if="variableFormats.length === 1"
        type="button"
        @click="copyFormat(variableFormats[0])"
        class="btn btn-link btn-sm p-0 d-inline-flex align-items-center gap-1 text-decoration-none"
        style="font-size:.75rem"
        :title="`Copy: ${variableFormats[0]}`"
      >
        <CopyIcon style="width:11px;height:11px" />
        <code class="text-primary" style="font-size:.7rem">{{ variableFormats[0] }}</code>
      </button>

      <!-- Variable copy: multiple formats — dropdown -->
      <div v-else-if="variableFormats.length > 1" class="position-relative" ref="dropdownRef">
        <button
          type="button"
          @click="showDropdown = !showDropdown"
          class="btn btn-link btn-sm p-0 d-inline-flex align-items-center gap-1 text-decoration-none"
          style="font-size:.75rem"
        >
          <CopyIcon style="width:11px;height:11px" />
          <span>Variables</span>
          <ChevronDownIcon style="width:11px;height:11px" />
        </button>
        <div
          v-if="showDropdown"
          class="uim-dropdown-menu"
          style="top:100%;right:0;left:auto;min-width:max-content"
        >
          <button
            v-for="fmt in variableFormats"
            :key="fmt"
            type="button"
            @click="copyFormat(fmt); showDropdown = false"
            class="uim-dropdown-item"
            style="font-family:monospace;font-size:.75rem;padding:.25rem .75rem"
          >
            {{ fmt }}
          </button>
        </div>
      </div>
    </div>

    <!-- Translatable field: Bootstrap 5 nav-tabs + input per locale -->
    <div v-if="field.translatable" class="border rounded overflow-hidden">
      <div class="uim-nav-tabs" style="background:#f8f9fa;padding:.375rem .5rem;border-bottom:1px solid #dee2e6">
        <button
          v-for="locale in locales"
          :key="locale"
          type="button"
          @click="activeLocale = locale"
          class="uim-nav-link text-uppercase"
          :class="{ active: activeLocale === locale }"
          style="font-size:.7rem;letter-spacing:.05em;padding:.35rem .75rem"
        >
          {{ locale }}
        </button>
      </div>
      <div class="p-2">
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

    <div v-if="field.help" class="uim-form-text">{{ field.help }}</div>
    <div v-if="error" class="uim-invalid-feedback">
      <AlertCircleIcon style="width:12px;height:12px;flex-shrink:0" />
      {{ error }}
    </div>
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

const showDropdown = ref(false)
const dropdownRef = ref(null)

function handleOutsideClick(e) {
  if (dropdownRef.value && !dropdownRef.value.contains(e.target)) {
    showDropdown.value = false
  }
}

onMounted(() => document.addEventListener('click', handleOutsideClick, true))
onUnmounted(() => document.removeEventListener('click', handleOutsideClick, true))

const localeValue = computed(() => {
  const val = props.modelValue
  if (val && typeof val === 'object' && !Array.isArray(val)) return val[activeLocale.value] ?? ''
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
