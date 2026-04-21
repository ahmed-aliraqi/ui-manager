<template>
  <div class="space-y-2">
    <!-- Existing file(s) -->
    <div v-if="files.length" class="space-y-1.5">
      <div
        v-for="(file, idx) in files"
        :key="idx"
        class="flex items-center gap-2 rounded-md border bg-muted/30 px-3 py-2 text-sm"
      >
        <FileIcon class="w-4 h-4 text-muted-foreground shrink-0" />
        <a :href="file.url" target="_blank" class="flex-1 truncate text-primary hover:underline text-xs">
          {{ file.filename || file.url }}
        </a>
        <button type="button" @click="removeFile(idx)" class="text-muted-foreground hover:text-destructive">
          <XIcon class="w-3.5 h-3.5" />
        </button>
      </div>
    </div>

    <!-- Upload button -->
    <div>
      <button
        type="button"
        @click="fileInput?.click()"
        :disabled="uploading"
        class="inline-flex items-center gap-2 px-3 py-1.5 rounded-md border text-sm hover:bg-muted transition-colors disabled:opacity-50"
      >
        <UploadIcon class="w-3.5 h-3.5" />
        {{ field.multiple ? 'Add file(s)' : 'Upload file' }}
      </button>

      <input
        ref="fileInput"
        type="file"
        :accept="field.accept?.join(',') || '*'"
        :multiple="field.multiple"
        class="hidden"
        @change="onFileChange"
      />
    </div>

    <p v-if="uploading" class="text-xs text-muted-foreground flex items-center gap-1">
      <LoaderIcon class="w-3 h-3 animate-spin" /> Uploading…
    </p>
    <p v-if="error" class="text-xs text-destructive">{{ error }}</p>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { FileIcon, UploadIcon, XIcon, LoaderIcon } from 'lucide-vue-next'
import { api } from '../../composables/useApi.js'

const props = defineProps({ id: String, field: Object, modelValue: { default: null } })
const emit = defineEmits(['update:modelValue'])

const fileInput = ref(null)
const uploading = ref(false)
const error = ref(null)

const files = computed(() => {
  if (!props.modelValue) return []
  return Array.isArray(props.modelValue) ? props.modelValue : [props.modelValue]
})

async function onFileChange(e) {
  error.value = null
  uploading.value = true
  try {
    const uploadedFiles = []
    for (const file of e.target.files) {
      const form = new FormData()
      form.append('file', file)
      form.append('collection', 'files')
      const { data } = await api.post('/media', form, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      uploadedFiles.push({ url: data.data.url, filename: data.data.filename, id: data.data.id })
    }
    const current = files.value
    const newValue = props.field.multiple
      ? [...current, ...uploadedFiles]
      : uploadedFiles[0]
    emit('update:modelValue', newValue)
  } catch (e) {
    error.value = e.message
  } finally {
    uploading.value = false
  }
}

function removeFile(idx) {
  const newFiles = files.value.filter((_, i) => i !== idx)
  emit('update:modelValue', props.field.multiple ? newFiles : null)
}
</script>
