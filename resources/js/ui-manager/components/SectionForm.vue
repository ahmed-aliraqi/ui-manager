<template>
  <div class="max-w-2xl">
    <!-- Loading skeleton while fetching section data -->
    <SkeletonLoader v-if="loading" :count="definition.fields.length || 3" />

    <form v-else @submit.prevent="handleSave" class="space-y-6" ref="formRef">
      <div
        v-for="field in definition.fields"
        :key="field.name"
        class="space-y-1.5"
      >
        <FieldRenderer
          :field="field"
          :modelValue="form[field.name]"
          :error="fieldErrors[field.name] ?? null"
          @update:modelValue="onFieldUpdate(field.name, $event)"
        />
      </div>

      <div class="flex items-center gap-3 pt-2 border-t">
        <button
          type="submit"
          :disabled="saving"
          class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground rounded-lg text-sm font-medium hover:bg-primary/90 disabled:opacity-50 transition-colors"
        >
          <SaveIcon v-if="!saving" class="w-4 h-4" />
          <LoaderIcon v-else class="w-4 h-4 animate-spin" />
          {{ saving ? 'Saving…' : 'Save changes' }}
        </button>

        <span
          v-if="isDirty"
          class="text-xs text-muted-foreground"
        >Unsaved changes</span>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, onUnmounted, provide, watch } from 'vue'
import { SaveIcon, LoaderIcon } from 'lucide-vue-next'
import { useUiStore } from '../stores/ui.js'
import { useToast } from '../composables/useToast.js'
import { api } from '../composables/useApi.js'
import { useLocales } from '../composables/useConfig.js'
import FieldRenderer from './fields/FieldRenderer.vue'
import SkeletonLoader from './SkeletonLoader.vue'

const props = defineProps({
  page:       String,
  section:    String,
  definition: Object,
})

provide('sectionName', props.section)

const store                 = useUiStore()
const { toast }             = useToast()
const { locales, defaultLocale } = useLocales()
const form                  = reactive({})
const fieldErrors           = reactive({})
const saving                = ref(false)
const loading               = ref(true)
const isDirty               = ref(false)
const formRef               = ref(null)

// Suppress the first watch trigger that fires during initialisation
let initialising = true

watch(form, () => {
  if (!initialising) isDirty.value = true
}, { deep: true })

function onFieldUpdate(name, value) {
  form[name] = value
  delete fieldErrors[name]
}

function initFieldValue(fieldDef, storedValue) {
  if (!fieldDef.translatable) {
    return storedValue ?? fieldDef.default ?? null
  }

  // DB value takes priority — already a locale-keyed object
  if (storedValue && typeof storedValue === 'object' && !Array.isArray(storedValue)) {
    const obj = {}
    locales.forEach(l => { obj[l] = storedValue[l] ?? '' })
    return obj
  }

  // No DB value — fall back to field default
  const dflt = fieldDef.default
  const obj = {}
  if (dflt && typeof dflt === 'object' && !Array.isArray(dflt)) {
    // Default is already locale-keyed: { en: 'Title', ar: 'العنوان' }
    locales.forEach(l => { obj[l] = dflt[l] ?? '' })
  } else {
    // Default is a plain string (or null/undefined)
    locales.forEach(l => { obj[l] = l === defaultLocale ? (dflt ?? '') : '' })
  }
  return obj
}

async function loadSection() {
  loading.value = true
  try {
    const data = await store.fetchSection(props.page, props.section)
    const fields = data.fields || {}
    props.definition.fields.forEach(f => {
      form[f.name] = initFieldValue(f, fields[f.name])
    })
  } catch {
    props.definition.fields.forEach(f => {
      form[f.name] = initFieldValue(f, undefined)
    })
  } finally {
    loading.value = false
    await new Promise(r => requestAnimationFrame(r))
    initialising = false
  }
}

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
    await store.saveSectionFields(props.page, props.section, fields)
    Object.assign(form, fields)
    isDirty.value = false
    toast({ title: 'Saved', variant: 'success' })
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

// Keyboard shortcut: Ctrl+S / Cmd+S
function onKeyDown(e) {
  if ((e.metaKey || e.ctrlKey) && e.key === 's') {
    e.preventDefault()
    if (!saving.value) handleSave()
  }
}

// Warn on browser close/refresh when dirty
function onBeforeUnload(e) {
  if (isDirty.value) {
    e.preventDefault()
    e.returnValue = ''
  }
}

onMounted(() => {
  loadSection()
  document.addEventListener('keydown', onKeyDown)
  window.addEventListener('beforeunload', onBeforeUnload)
})

onUnmounted(() => {
  document.removeEventListener('keydown', onKeyDown)
  window.removeEventListener('beforeunload', onBeforeUnload)
})
</script>
