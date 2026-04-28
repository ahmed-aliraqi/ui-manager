<template>
  <div>
    <!-- Preview -->
    <div v-if="previewUrl" class="position-relative d-inline-block mb-2">
      <img
        :src="previewUrl"
        class="img-thumbnail"
        style="max-height:8rem;width:auto;"
        :alt="field.label"
      />
      <button
        type="button"
        @click="clear"
        class="position-absolute top-0 end-0 btn btn-danger btn-sm rounded-circle p-0 d-flex align-items-center justify-content-center"
        style="width:20px;height:20px;transform:translate(50%,-50%);"
      >
        <XIcon style="width:0.75rem;height:0.75rem;" />
      </button>
      <span
        v-if="modelValue?._pending"
        class="position-absolute bottom-0 start-0 badge text-bg-warning m-1"
      >pending</span>
    </div>

    <!-- Upload zone -->
    <div
      v-if="!previewUrl"
      @dragover.prevent="dragging = true"
      @dragleave="dragging = false"
      @drop.prevent="onDrop"
      class="uim-upload-zone"
      :class="{ 'uim-upload-zone--drag': dragging }"
      @click="fileInput?.click()"
    >
      <UploadIcon class="mb-2 text-muted" style="width:1.5rem;height:1.5rem;" />
      <p class="text-muted small mb-1">
        <span class="fw-medium" style="color:#6c757d">Click to upload</span> or drag &amp; drop
      </p>
      <p class="text-muted small mb-0">
        {{ field.accept?.join(', ') || 'Images' }} &middot; Max {{ Math.round((field.max_size || 5120) / 1024) }}MB
      </p>
    </div>

    <input
      ref="fileInput"
      type="file"
      :accept="field.accept?.join(',') || 'image/*'"
      class="d-none"
      @change="onFileChange"
    />

    <p v-if="error" class="text-danger small mt-1">{{ error }}</p>
  </div>
</template>

<script setup>
import { ref, computed, onUnmounted } from 'vue'
import { UploadIcon, XIcon } from 'lucide-vue-next'

const props = defineProps({ id: String, field: Object, modelValue: { default: null } })
const emit = defineEmits(['update:modelValue'])

const fileInput = ref(null)
const dragging = ref(false)
const error = ref(null)

const blobUrls = new Set()

const previewUrl = computed(() => {
  if (!props.modelValue) return null
  if (props.modelValue?._pending) return props.modelValue.localUrl
  if (typeof props.modelValue === 'string') return props.modelValue
  return props.modelValue?.url || null
})

onUnmounted(() => {
  blobUrls.forEach(url => URL.revokeObjectURL(url))
})

function onDrop(e) {
  dragging.value = false
  const file = e.dataTransfer.files[0]
  if (file) setPendingFile(file)
}

function onFileChange(e) {
  const file = e.target.files[0]
  if (file) setPendingFile(file)
}

function setPendingFile(file) {
  error.value = null
  if (props.modelValue?._pending && props.modelValue.localUrl) {
    URL.revokeObjectURL(props.modelValue.localUrl)
    blobUrls.delete(props.modelValue.localUrl)
  }
  const localUrl = URL.createObjectURL(file)
  blobUrls.add(localUrl)
  emit('update:modelValue', {
    _pending: true,
    file,
    localUrl,
    existingMediaId: props.modelValue?.id ?? null,
  })
}

function clear() {
  if (props.modelValue?._pending && props.modelValue.localUrl) {
    URL.revokeObjectURL(props.modelValue.localUrl)
    blobUrls.delete(props.modelValue.localUrl)
  }
  emit('update:modelValue', null)
  if (fileInput.value) fileInput.value.value = ''
}
</script>

<style scoped>
.uim-upload-zone {
  border: 2px dashed #ced4da;
  border-radius: .375rem;
  padding: 1.5rem 1rem;
  text-align: center;
  cursor: pointer;
  background: #fff;
  transition: border-color .15s ease-in-out, background-color .15s ease-in-out;
}
.uim-upload-zone:hover {
  border-color: #6c757d;
  background: #f8f9fa;
}
.uim-upload-zone--drag {
  border-color: #6c757d;
  background: #f8f9fa;
}
</style>
