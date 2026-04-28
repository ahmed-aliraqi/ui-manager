<template>
  <div>
    <!-- Existing file(s) -->
    <div v-if="files.length" class="mb-2">
      <div
        v-for="(file, idx) in files"
        :key="idx"
        class="d-flex align-items-center gap-2 border rounded px-3 py-2 mb-1 bg-light"
      >
        <FileIcon class="flex-shrink-0" style="width:1rem;height:1rem;" />
        <a :href="file.url" target="_blank" class="text-primary text-truncate flex-grow-1" style="font-size:0.75rem;">
          {{ file.filename || file.url }}
        </a>
        <button type="button" @click="removeFile(idx)" class="btn btn-link btn-sm text-danger p-0 ms-auto">
          <XIcon style="width:0.875rem;height:0.875rem;" />
        </button>
      </div>
    </div>

    <!-- Upload button -->
    <div>
      <button
        type="button"
        @click="fileInput?.click()"
        :disabled="uploading"
        class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-2"
      >
        <span v-if="uploading" class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
        <UploadIcon v-else style="width:0.875rem;height:0.875rem;" />
        {{ field.multiple ? 'Add file(s)' : 'Upload file' }}
      </button>

      <input
        ref="fileInput"
        type="file"
        :accept="field.accept?.join(',') || '*'"
        :multiple="field.multiple"
        class="d-none"
        @change="onFileChange"
      />
    </div>

    <p v-if="uploading" class="text-muted small d-flex align-items-center gap-1 mt-1">
      Uploading…
    </p>
    <p v-if="error" class="text-danger small mt-1">{{ error }}</p>
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
