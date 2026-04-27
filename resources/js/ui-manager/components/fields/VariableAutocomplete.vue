<template>
  <div
    v-if="active && showDropdown && filtered.length"
    class="absolute left-0 right-0 top-full z-50 mt-1 rounded-md border bg-popover text-popover-foreground shadow-md overflow-hidden"
  >
    <div class="p-1">
      <button
        v-for="(v, idx) in filtered"
        :key="v.key"
        :ref="el => { if (el) itemRefs[idx] = el }"
        type="button"
        @mousedown.prevent="insert(v.placeholder)"
        @mousemove="selectedIndex = idx"
        class="w-full text-left px-3 py-1.5 rounded text-xs transition-colors flex items-center gap-2"
        :class="idx === selectedIndex ? 'bg-accent text-accent-foreground' : 'hover:bg-accent'"
      >
        <code class="text-primary font-mono">{{ v.placeholder }}</code>
        <span class="text-muted-foreground truncate">{{ v.key }}</span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useUiStore } from '../../stores/ui.js'

const props = defineProps({
  value:  String,
  active: Boolean,
})
const emit = defineEmits(['insert'])

const store        = useUiStore()
const selectedIndex = ref(-1)
const itemRefs      = ref([])

function findOpenPercent(str) {
  let openPos = -1
  let inVar   = false
  for (let i = 0; i < str.length; i++) {
    if (str[i] === '%') {
      if (!inVar) { inVar = true;  openPos = i }
      else        { inVar = false; openPos = -1 }
    }
  }
  return openPos
}

const openPos     = computed(() => props.value ? findOpenPercent(props.value) : -1)
const showDropdown = computed(() => openPos.value !== -1)
const searchTerm   = computed(() =>
  openPos.value !== -1 ? props.value.slice(openPos.value + 1).toLowerCase() : ''
)
const filtered = computed(() =>
  store.variables
    .filter(v => v.key.toLowerCase().includes(searchTerm.value))
    .slice(0, 8)
)

watch(filtered, () => { selectedIndex.value = -1 })
watch(() => props.active, (val) => { if (!val) selectedIndex.value = -1 })

function insert(placeholder) {
  if (openPos.value === -1) return
  emit('insert', props.value.slice(0, openPos.value) + placeholder)
  selectedIndex.value = -1
}

function navigate(dir) {
  if (!filtered.value.length) return
  const len = filtered.value.length
  selectedIndex.value = (selectedIndex.value + dir + len) % len
  itemRefs.value[selectedIndex.value]?.scrollIntoView({ block: 'nearest' })
}

function confirmSelection() {
  if (selectedIndex.value >= 0 && filtered.value[selectedIndex.value]) {
    insert(filtered.value[selectedIndex.value].placeholder)
    return true
  }
  return false
}

defineExpose({ navigate, confirmSelection })
</script>
