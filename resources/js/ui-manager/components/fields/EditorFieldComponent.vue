<template>
  <div class="rounded-md border border-input bg-background overflow-hidden">
    <!-- Toolbar -->
    <div class="flex flex-wrap gap-0.5 p-1.5 border-b bg-muted/30">
      <button
        v-for="btn in toolbar"
        :key="btn.action"
        type="button"
        @click="btn.fn()"
        :class="[
          'p-1.5 rounded text-sm hover:bg-muted transition-colors',
          btn.active?.() ? 'bg-muted text-foreground' : 'text-muted-foreground'
        ]"
        :title="btn.label"
      >
        <component :is="btn.icon" class="w-3.5 h-3.5" />
      </button>
    </div>

    <!-- Content area -->
    <div
      ref="editorEl"
      contenteditable="true"
      @input="onInput"
      @keydown="onKeydown"
      class="min-h-[120px] p-3 text-sm outline-none prose prose-sm max-w-none"
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
