<template>
  <div style="border:1px solid #ced4da;border-radius:.375rem;overflow:hidden">
    <!-- Toolbar -->
    <div class="d-flex flex-wrap gap-1 p-2 border-bottom bg-light">
      <button
        v-for="btn in toolbar"
        :key="btn.action"
        type="button"
        @click="btn.fn()"
        :class="[
          'btn btn-sm btn-light p-1',
          btn.active?.() ? 'btn-secondary' : ''
        ]"
        :title="btn.label"
      >
        <component :is="btn.icon" style="width:0.875rem;height:0.875rem;" />
      </button>
    </div>

    <!-- Content area -->
    <div
      ref="editorEl"
      contenteditable="true"
      @input="onInput"
      @keydown="onKeydown"
      class="p-3 bg-white"
      style="min-height:120px;outline:none;"
      v-html="localHtml"
    />
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import {
  BoldIcon, ItalicIcon, Heading2Icon, ListIcon,
  ListOrderedIcon, LinkIcon
} from 'lucide-vue-next'

const props = defineProps({ id: String, field: Object, modelValue: { default: '' } })
const emit = defineEmits(['update:modelValue'])

const editorEl = ref(null)
const localHtml = ref(props.modelValue || '')

onMounted(() => {
  if (editorEl.value) editorEl.value.innerHTML = localHtml.value
})

watch(() => props.modelValue, (val) => {
  if (val !== editorEl.value?.innerHTML) {
    localHtml.value = val || ''
    if (editorEl.value) editorEl.value.innerHTML = localHtml.value
  }
})

function onInput() {
  emit('update:modelValue', editorEl.value.innerHTML)
}

function onKeydown(e) {
  // Tab key: insert 2 spaces
  if (e.key === 'Tab') {
    e.preventDefault()
    document.execCommand('insertText', false, '  ')
  }
}

function exec(command, value = null) {
  editorEl.value?.focus()
  document.execCommand(command, false, value)
  emit('update:modelValue', editorEl.value.innerHTML)
}

function isActive(command) {
  return document.queryCommandState(command)
}

const toolbar = [
  { action: 'bold',        label: 'Bold',          icon: BoldIcon,         fn: () => exec('bold'),          active: () => isActive('bold') },
  { action: 'italic',      label: 'Italic',        icon: ItalicIcon,       fn: () => exec('italic'),        active: () => isActive('italic') },
  { action: 'heading',     label: 'Heading',       icon: Heading2Icon,     fn: () => exec('formatBlock', 'h2') },
  { action: 'ul',          label: 'Bullet list',   icon: ListIcon,         fn: () => exec('insertUnorderedList') },
  { action: 'ol',          label: 'Ordered list',  icon: ListOrderedIcon,  fn: () => exec('insertOrderedList') },
  { action: 'link',        label: 'Link',          icon: LinkIcon,         fn: () => {
    const url = prompt('Enter URL:')
    if (url) exec('createLink', url)
  }},
]
</script>
