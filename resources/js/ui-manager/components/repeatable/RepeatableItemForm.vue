<template>
  <form @submit.prevent="handleSave" class="space-y-4">
    <div
      v-for="field in definition.fields"
      :key="field.name"
    >
      <FieldRenderer
        :field="field"
        :modelValue="form[field.name]"
        @update:modelValue="form[field.name] = $event"
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
      <span v-if="saved" class="text-xs text-green-600">✓ Saved</span>
      <span v-if="error" class="text-xs text-destructive">{{ error }}</span>
    </div>
  </form>
</template>

<script setup>
import { ref, reactive, computed, onMounted, provide } from 'vue'
import { SaveIcon, LoaderIcon } from 'lucide-vue-next'
import { useUiStore } from '../../stores/ui.js'
import { api } from '../../composables/useApi.js'
import FieldRenderer from '../fields/FieldRenderer.vue'

const props = defineProps({
  definition: Object,
  item: { default: null },
  page: String,
  section: String,
})

const emit = defineEmits(['saved', 'cancel'])

provide('sectionName', props.section)

const store = useUiStore()
const form = reactive({})
const saving = ref(false)
const saved = ref(false)
const error = ref(null)

// item===null     → blank add-new form (Cancel button shown)
// item.id===null  → default item pre-filled (no Cancel, calls addItem on save)
// item.id!==null  → persisted item (calls updateItem on save)
const isBlankNew = computed(() => props.item === null)
const isNew = computed(() => !props.item?.id)

onMounted(() => {
  props.definition.fields.forEach(f => {
    form[f.name] = props.item?.fields?.[f.name] ?? f.default ?? null
  })
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
  saved.value = false
  error.value = null
  try {
    const fields = await resolvePendingUploads({ ...form })
    let result
    if (isNew.value) {
      result = await store.addItem(props.page, props.section, fields)
    } else {
      result = await store.updateItem(props.page, props.section, props.item.id, fields)
    }
    // Sync form state so pending image values become real uploaded values
    Object.assign(form, fields)
    saved.value = true
    emit('saved', result)
    setTimeout(() => { saved.value = false }, 2000)
  } catch (e) {
    error.value = e.message
  } finally {
    saving.value = false
  }
}
</script>
