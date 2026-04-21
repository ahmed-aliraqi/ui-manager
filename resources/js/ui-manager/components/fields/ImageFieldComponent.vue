<template>
  <div class="space-y-2">
    <!-- Preview -->
    <div
      v-if="previewUrl"
      class="relative inline-block"
    >
      <img
        :src="previewUrl"
        class="h-32 w-auto rounded-md border object-cover"
        :alt="field.label"
      />
      <button
        type="button"
        @click="clear"
        class="absolute -top-2 -right-2 w-5 h-5 rounded-full bg-destructive text-destructive-foreground flex items-center justify-center hover:opacity-90"
      >
        <XIcon class="w-3 h-3" />
      </button>
    </div>

    <!-- Upload zone -->
    <div
      v-if="!previewUrl"
      @dragover.prevent="dragging = true"
      @dragleave="dragging = false"
      @drop.prevent="onDrop"
      :class="[
        'flex flex-col items-center justify-center rounded-lg border-2 border-dashed p-6 cursor-pointer transition-colors',
        dragging ? 'border-primary bg-primary/5' : 'border-muted-foreground/25 hover:border-primary/50',
      ]"
      @click="fileInput?.click()"
    >
      <UploadIcon class="w-6 h-6 text-muted-foreground mb-2" />
      <p class="text-sm text-muted-foreground">
        <span class="font-medium text-primary">Click to upload</span> or drag & drop
      </p>
      <p class="text-xs text-muted-foreground mt-1">
        {{ field.accept?.join(', ') || 'Images' }} · Max {{ Math.round((field.max_size || 5120) / 1024) }}MB
      </p>
    </div>

    <input
      ref="fileInput"
      type="file"
      :accept="field.accept?.join(',') || 'image/*'"
      class="hidden"
      @change="onFileChange"
    />

    <p v-if="uploading" class="text-xs text-muted-foreground flex items-center gap-1">
      <LoaderIcon class="w-3 h-3 animate-spin" /> Uploading…
    </p>
    <p v-if="error" class="text-xs text-destructive">{{ error }}</p>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { UploadIcon, XIcon, LoaderIcon } from 'lucide-vue-next'
import { api } from '../../composables/useApi.js'

const props = defineProps({ id: String, field: Object, modelValue: { default: null } })
const emit = defineEmits(['update:modelValue'])

const fileInput = ref(null)
const uploading = ref(false)
const dragging = ref(false)
const error = ref(null)

const previewUrl = computed(() => {
  if (!props.modelValue) return null
  if (typeof props.modelValue === 'string') return props.modelValue
  return props.modelValue?.url || null
})

function onDrop(e) {
  dragging.value = false
  const file = e.dataTransfer.files[0]
  if (file) uploadFile(file)
}

function onFileChange(e) {
  const file = e.target.files[0]
  if (file) uploadFile(file)
}

async function uploadFile(file) {
  error.value = null
  uploading.value = true
  try {
    const form = new FormData()
    form.append('file', file)
    form.append('collection', 'images')
    const { data } = await api.post('/media', form, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    emit('update:modelValue', { url: data.data.url, id: data.data.id, filename: data.data.filename })
  } catch (e) {
    error.value = e.message
  } finally {
    uploading.value = false
  }
}

function clear() {
  emit('update:modelValue', null)
  if (fileInput.value) fileInput.value.value = ''
}
</script>
