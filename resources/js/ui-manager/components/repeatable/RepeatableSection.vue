<template>
  <div>
    <!-- Items list -->
    <div v-if="items.length" class="space-y-3 mb-4">
      <div
        v-for="(item, idx) in items"
        :key="itemKey(item, idx)"
        draggable="true"
        @dragstart="onDragStart(idx, $event)"
        @dragover.prevent="onDragOver(idx)"
        @dragend="onDragEnd"
        :class="[
          'rounded-xl border bg-card overflow-hidden transition-opacity',
          dragIndex === idx ? 'opacity-40' : 'opacity-100',
        ]"
      >
        <div class="flex items-center gap-2 px-4 py-3 bg-muted/30 border-b">
          <GripVerticalIcon class="w-4 h-4 text-muted-foreground cursor-grab shrink-0" />
          <span class="text-sm font-medium flex-1 truncate">
            {{ itemLabel(item, idx) }}
          </span>
          <span
            v-if="item.id === null"
            class="text-xs text-amber-600 bg-amber-50 border border-amber-200 rounded px-1.5 py-0.5 shrink-0"
          >default</span>
          <button
            type="button"
            @click="toggleExpand(itemKey(item, idx))"
            class="text-muted-foreground hover:text-foreground transition-colors shrink-0"
          >
            <ChevronDownIcon
              class="w-4 h-4 transition-transform duration-150"
              :class="{ 'rotate-180': expanded.has(itemKey(item, idx)) }"
            />
          </button>
          <button
            v-if="item.id !== null"
            type="button"
            @click="deleteItem(item, idx)"
            class="text-muted-foreground hover:text-destructive transition-colors shrink-0"
          >
            <Trash2Icon class="w-4 h-4" />
          </button>
        </div>

        <Transition name="slide">
          <div v-if="expanded.has(itemKey(item, idx))" class="p-4">
            <RepeatableItemForm
              :definition="definition"
              :item="item"
              :page="page"
              :section="section"
              @saved="onItemSaved(idx, $event)"
            />
          </div>
        </Transition>
      </div>
    </div>

    <div v-else class="rounded-xl border border-dashed p-8 text-center text-muted-foreground mb-4">
      <ListIcon class="w-6 h-6 mx-auto mb-2 opacity-40" />
      <p class="text-sm">No items yet — add your first one below.</p>
    </div>

    <!-- Add new item -->
    <button
      type="button"
      @click="showAddForm = !showAddForm"
      class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-dashed text-sm text-muted-foreground hover:border-primary hover:text-primary transition-colors"
    >
      <PlusIcon class="w-4 h-4" />
      Add item
    </button>

    <Transition name="slide">
      <div v-if="showAddForm" class="mt-4 rounded-xl border bg-card p-4">
        <p class="text-sm font-medium mb-4">New item</p>
        <RepeatableItemForm
          :definition="definition"
          :item="null"
          :page="page"
          :section="section"
          @saved="onNewItemSaved"
          @cancel="showAddForm = false"
        />
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import {
  GripVerticalIcon, ChevronDownIcon, Trash2Icon,
  PlusIcon, ListIcon,
} from 'lucide-vue-next'
import { useUiStore } from '../../stores/ui.js'
import RepeatableItemForm from './RepeatableItemForm.vue'

const props = defineProps({
  page: String,
  section: String,
  definition: Object,
})

const store = useUiStore()
const items = ref([])
const expanded = reactive(new Set())
const showAddForm = ref(false)
const loading = ref(false)

// Drag-and-drop state
const dragIndex = ref(null)

onMounted(async () => {
  loading.value = true
  try {
    const data = await store.fetchSection(props.page, props.section)
    items.value = data.items ?? []

    // Auto-expand default (unsaved) items so they're immediately editable
    items.value.forEach((item, idx) => {
      if (item.id === null) expanded.add(itemKey(item, idx))
    })
  } catch {
    items.value = []
  } finally {
    loading.value = false
  }
})

function itemKey(item, idx) {
  return item.id !== null ? `id-${item.id}` : `default-${idx}`
}

function itemLabel(item, idx) {
  const fields = item.fields ?? {}
  const firstValue = Object.values(fields).find(v => typeof v === 'string' && v.trim() !== '')
  return firstValue ?? `Item ${idx + 1}`
}

function toggleExpand(key) {
  if (expanded.has(key)) expanded.delete(key)
  else expanded.add(key)
}

async function deleteItem(item, idx) {
  if (!confirm('Delete this item?')) return
  await store.deleteItem(props.page, props.section, item.id)
  items.value.splice(idx, 1)
}

function onItemSaved(idx, updated) {
  items.value[idx] = { ...items.value[idx], ...updated }
  if (updated.id !== null) {
    expanded.delete(itemKey({ id: null }, idx))
    expanded.add(`id-${updated.id}`)
  }
}

function onNewItemSaved(newItem) {
  items.value.push(newItem)
  showAddForm.value = false
}

// ------------------------------------------------------------------ drag & drop

function onDragStart(idx, e) {
  dragIndex.value = idx
  e.dataTransfer.effectAllowed = 'move'
}

function onDragOver(targetIdx) {
  if (dragIndex.value === null || dragIndex.value === targetIdx) return
  // Reorder in-place so the visual moves as you drag
  const arr = [...items.value]
  const [moved] = arr.splice(dragIndex.value, 1)
  arr.splice(targetIdx, 0, moved)
  items.value = arr
  dragIndex.value = targetIdx
}

async function onDragEnd() {
  dragIndex.value = null
  // Persist only items that are already saved (have a real DB id)
  const ids = items.value.filter(i => i.id !== null).map(i => i.id)
  if (ids.length > 0) {
    try {
      await store.reorderItems(props.page, props.section, ids)
    } catch {
      // Non-fatal: order change failed, but local state already reflects it
    }
  }
}
</script>

<style scoped>
.slide-enter-active, .slide-leave-active {
  transition: all 0.18s ease;
  overflow: hidden;
}
.slide-enter-from, .slide-leave-to {
  max-height: 0;
  opacity: 0;
}
.slide-enter-to, .slide-leave-from {
  max-height: 1200px;
  opacity: 1;
}
</style>
