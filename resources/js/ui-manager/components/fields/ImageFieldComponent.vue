<template>
  <div class="space-y-2">
    <!-- Preview -->
    <div v-if="previewUrl" class="relative inline-block">
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
      <span
        v-if="modelValue?._pending"
        class="absolute bottom-1 left-1 text-[10px] bg-amber-500 text-white rounded px-1"
      >pending</span>
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

    <p v-if="error" class="text-xs text-destructive">{{ error }}</p>
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

// Keep track of locally-created blob URLs so we can revoke them on cleanup
const blobUrls = new Set()

const previewUrl = computed(() => {
  if (!props.modelValue) return null
  // Locally-selected file not yet uploaded
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

  // Revoke the previous local blob URL to avoid memory leaks
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
    // Preserve the current media ID so the server can replace-in-place via singleFile()
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
