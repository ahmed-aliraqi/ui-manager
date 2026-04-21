<template>
  <div
    v-if="showDropdown && filtered.length"
    class="absolute left-0 right-0 top-full z-50 mt-1 rounded-md border bg-popover text-popover-foreground shadow-md overflow-hidden"
  >
    <div class="p-1">
      <button
        v-for="v in filtered"
        :key="v.key"
        type="button"
        @mousedown.prevent="insert(v.placeholder)"
        class="w-full text-left px-3 py-1.5 rounded text-xs hover:bg-accent transition-colors flex items-center gap-2"
      >
        <code class="text-primary font-mono">{{ v.placeholder }}</code>
        <span class="text-muted-foreground truncate">{{ v.key }}</span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useUiStore } from '../../stores/ui.js'

const props = defineProps({ value: String })
const emit = defineEmits(['insert'])

const store = useUiStore()

const showDropdown = computed(() => {
  if (!props.value) return false
  const lastPercent = props.value.lastIndexOf('%')
  if (lastPercent === -1) return false
  const afterPercent = props.value.slice(lastPercent + 1)
  return !afterPercent.includes('%') && afterPercent.length > 0
})

const searchTerm = computed(() => {
  if (!props.value) return ''
  const lastPercent = props.value.lastIndexOf('%')
  return props.value.slice(lastPercent + 1).toLowerCase()
})

const filtered = computed(() =>
  store.variables
    .filter(v => v.key.toLowerCase().includes(searchTerm.value))
    .slice(0, 8)
)

function insert(placeholder) {
  const lastPercent = props.value.lastIndexOf('%')
  const before = props.value.slice(0, lastPercent)
  emit('insert', before + placeholder)
}
</script>
