<template>
  <form @submit.prevent="handleSave" class="space-y-4" ref="formRef">
    <div
      v-for="field in definition.fields"
      :key="field.name"
    >
      <FieldRenderer
        :field="field"
        :modelValue="form[field.name]"
        :error="fieldErrors[field.name] ?? null"
        @update:modelValue="onFieldUpdate(field.name, $event)"
      />
    </div>

    <div class="flex items-center gap-2 pt-2 border-t">
      <button
        type="submit"
        :disabled="saving"
        class="inline-flex items-center gap-2 px-3 py-1.5 bg-primary text-primary-foreground rounded-md text-sm font-medium hover:bg-primary/90 disabled:opacity-50 transition-colors"
      >
        <SaveIcon v-if="!saving" class="w-3.5 h-3.5" />
        <LoaderIcon v-else class="w-3.5 h-3.5 animate-spin" />
        Save
      </button>
      <button
        v-if="isBlankNew"
        type="button"
        @click="$emit('cancel')"
        class="px-3 py-1.5 rounded-md text-sm text-muted-foreground hover:text-foreground border transition-colors"
      >
        Cancel
      </button>
    </div>
  </form>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, provide } from 'vue'
import { SaveIcon, LoaderIcon } from 'lucide-vue-next'
import { useUiStore } from '../../stores/ui.js'
import { useToast } from '../../composables/useToast.js'
import { useLocales } from '../../composables/useConfig.js'
import { api } from '../../composables/useApi.js'
import FieldRenderer from '../fields/FieldRenderer.vue'

const props = defineProps({
  definition: Object,
  item:       { default: null },
  page:       String,
  section:    String,
})

const emit = defineEmits(['saved', 'cancel'])

provide('sectionName', props.section)

const store       = useUiStore()
const { toast }   = useToast()
const { locales, defaultLocale } = useLocales()
const form        = reactive({})
const fieldErrors = reactive({})
const saving      = ref(false)
const formRef     = ref(null)

const isBlankNew = computed(() => props.item === null)
const isNew      = computed(() => !props.item?.id)

function onFieldUpdate(name, value) {
  form[name] = value
  delete fieldErrors[name]
}

function initFieldValue(fieldDef, storedValue) {
  if (!fieldDef.translatable) {
    return storedValue ?? fieldDef.default ?? null
  }

  // Stored value is already a locale-keyed object
  if (storedValue && typeof storedValue === 'object' && !Array.isArray(storedValue)) {
    const obj = {}
    locales.forEach(l => { obj[l] = storedValue[l] ?? '' })
    return obj
  }

  // Fall back to field default
  const dflt = fieldDef.default
  const obj = {}
  if (dflt && typeof dflt === 'object' && !Array.isArray(dflt)) {
    locales.forEach(l => { obj[l] = dflt[l] ?? '' })
  } else {
    locales.forEach(l => { obj[l] = l === defaultLocale ? (dflt ?? '') : '' })
  }
  return obj
}

onMounted(() => {
  props.definition.fields.forEach(f => {
    form[f.name] = initFieldValue(f, props.item?.fields?.[f.name])
  })
  document.addEventListener('keydown', onKeyDown)
})

onUnmounted(() => {
  document.removeEventListener('keydown', onKeyDown)
})

async function resolvePendingUploads(formData) {
  const resolved = { ...formData }
  for (const [key, value] of Object.entries(resolved)) {
    if (value && typeof value === 'object' && value._pending && value.file instanceof File) {
      const fd = new FormData()
      fd.append('file', value.file)
      if (value.existingMediaId) fd.append('existing_media_id', String(value.existingMediaId))
      const { data } = await api.post('/media', fd, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      resolved[key] = { id: data.data.id, url: data.data.url, filename: data.data.filename }
    }
  }
  return resolved
}

async function handleSave() {
  saving.value = true
  Object.keys(fieldErrors).forEach(k => delete fieldErrors[k])
  try {
    const fields = await resolvePendingUploads({ ...form })
    let result
    if (isNew.value) {
      result = await store.addItem(props.page, props.section, fields)
    } else {
      result = await store.updateItem(props.page, props.section, props.item.id, fields)
    }
    Object.assign(form, fields)
    toast({ title: 'Item saved', variant: 'success' })
    emit('saved', result)
  } catch (e) {
    const errors = e.response?.data?.errors ?? null
    if (errors) {
      Object.entries(errors).forEach(([key, msgs]) => {
        const fieldName = key.replace(/^fields\./, '')
        fieldErrors[fieldName] = Array.isArray(msgs) ? msgs[0] : msgs
      })
      toast({ title: 'Validation failed', description: 'Please fix the highlighted fields.', variant: 'error' })
    } else {
      toast({ title: 'Save failed', description: e.message, variant: 'error' })
    }
  } finally {
    saving.value = false
  }
}

// Ctrl+S / Cmd+S only when this form is focused
function onKeyDown(e) {
  if ((e.metaKey || e.ctrlKey) && e.key === 's') {
    if (formRef.value?.contains(document.activeElement)) {
      e.preventDefault()
      if (!saving.value) handleSave()
    }
  }
}
</script>
