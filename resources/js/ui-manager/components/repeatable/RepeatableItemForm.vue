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
        {{ isNew ? 'Add item' : 'Save' }}
      </button>
      <button
        v-if="isNew"
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
const isNew = computed(() => !props.item?.id)

onMounted(() => {
  props.definition.fields.forEach(f => {
    form[f.name] = props.item?.fields?.[f.name] ?? f.default ?? null
  })
})

async function handleSave() {
  saving.value = true
  saved.value = false
  error.value = null
  try {
    let result
    if (isNew.value) {
      result = await store.addItem(props.page, props.section, { ...form })
    } else {
      result = await store.updateItem(props.page, props.section, props.item.id, { ...form })
    }
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
