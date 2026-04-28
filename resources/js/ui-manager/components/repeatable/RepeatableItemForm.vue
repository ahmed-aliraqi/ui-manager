<template>
  <form @submit.prevent="handleSave" ref="formRef">
    <div v-for="field in definition.fields" :key="field.name">
      <FieldRenderer
        :field="field"
        :modelValue="form[field.name]"
        :error="fieldErrors[field.name] ?? null"
        @update:modelValue="onFieldUpdate(field.name, $event)"
      />
    </div>

    <div class="d-flex align-items-center gap-2 pt-2 border-top mt-2">
      <button
        type="submit"
        :disabled="saving"
        class="btn btn-primary btn-sm d-inline-flex align-items-center gap-2 px-3"
        style="border-radius:.375rem"
      >
        <span v-if="saving" class="spinner-border spinner-border-sm" role="status" aria-hidden="true" />
        <SaveIcon v-else style="width:.875rem;height:.875rem;" />
        Save
      </button>
      <button
        v-if="isBlankNew"
        type="button"
        @click="$emit('cancel')"
        class="btn btn-outline-secondary btn-sm"
      >
        Cancel
      </button>
    </div>
  </form>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, provide } from 'vue'
import { SaveIcon } from 'lucide-vue-next'
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
  layout:     { type: String, default: 'default' },
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

  if (storedValue && typeof storedValue === 'object' && !Array.isArray(storedValue)) {
    const obj = {}
    locales.forEach(l => { obj[l] = storedValue[l] ?? '' })
    return obj
  }

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
      result = await store.addItem(props.page, props.section, fields, props.layout)
    } else {
      result = await store.updateItem(props.page, props.section, props.item.id, fields, props.layout)
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

function onKeyDown(e) {
  if ((e.metaKey || e.ctrlKey) && e.key === 's') {
    if (formRef.value?.contains(document.activeElement)) {
      e.preventDefault()
      if (!saving.value) handleSave()
    }
  }
}
</script>
