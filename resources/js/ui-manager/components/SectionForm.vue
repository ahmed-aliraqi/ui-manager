<template>
  <div class="max-w-2xl">
    <form @submit.prevent="handleSave" class="space-y-6">
      <div
        v-for="field in definition.fields"
        :key="field.name"
        class="space-y-1.5"
      >
        <FieldRenderer
          :field="field"
          :modelValue="form[field.name]"
          @update:modelValue="form[field.name] = $event"
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

        <span v-if="saved" class="text-sm text-green-600 flex items-center gap-1">
          <CheckIcon class="w-4 h-4" /> Saved!
        </span>
        <span v-if="saveError" class="text-sm text-destructive">{{ saveError }}</span>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch } from 'vue'
import { SaveIcon, LoaderIcon, CheckIcon } from 'lucide-vue-next'
import { useUiStore } from '../stores/ui.js'
import FieldRenderer from './fields/FieldRenderer.vue'

const props = defineProps({
  page: String,
  section: String,
  definition: Object,
})

const store = useUiStore()
const form = reactive({})
const saving = ref(false)
const saved = ref(false)
const saveError = ref(null)

async function loadSection() {
  try {
    const data = await store.fetchSection(props.page, props.section)
    const fields = data.fields || {}
    props.definition.fields.forEach(f => {
      form[f.name] = fields[f.name] ?? f.default ?? null
    })
  } catch (e) {
    // fallback to defaults
    props.definition.fields.forEach(f => {
      form[f.name] = f.default ?? null
    })
  }
}

async function handleSave() {
  saving.value = true
  saved.value = false
  saveError.value = null
  try {
    await store.saveSectionFields(props.page, props.section, { ...form })
    saved.value = true
    setTimeout(() => { saved.value = false }, 2500)
  } catch (e) {
    saveError.value = e.message
  } finally {
    saving.value = false
  }
}

onMounted(loadSection)
</script>
