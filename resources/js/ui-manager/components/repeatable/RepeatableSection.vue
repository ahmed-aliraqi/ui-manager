<template>
  <div>
    <!-- Items list -->
    <div v-if="items.length" class="space-y-1 mb-4">
      <template v-for="(item, idx) in items" :key="itemKey(item, idx)">
        <!-- Drop insertion line ABOVE the item being dragged over -->
        <div
          v-if="dropTargetIdx === idx && dragIndex !== null && dragIndex !== idx"
          class="h-0.5 rounded bg-primary/60 mx-2 my-1 transition-all"
        />

        <div
          draggable="true"
          @dragstart="onDragStart(idx, $event)"
          @dragover.prevent="onDragOver(idx)"
          @dragleave="onDragLeave"
          @dragend="onDragEnd"
          :class="[
            'rounded-xl border bg-card overflow-hidden transition-all duration-150',
            dragIndex === idx ? 'opacity-40 scale-[0.98]' : 'opacity-100',
          ]"
        >
          <div class="flex items-center gap-2 px-4 py-3 bg-muted/30 border-b">
            <GripVerticalIcon class="w-4 h-4 text-muted-foreground cursor-grab active:cursor-grabbing shrink-0" />
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
              :aria-expanded="expanded.has(itemKey(item, idx))"
              :aria-label="expanded.has(itemKey(item, idx)) ? 'Collapse' : 'Expand'"
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
              aria-label="Delete item"
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
      </template>

      <!-- Drop insertion line at the END of the list -->
      <div
        v-if="dropTargetIdx === items.length && dragIndex !== null"
        class="h-0.5 rounded bg-primary/60 mx-2 my-1 transition-all"
      />
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
import { useToast } from '../../composables/useToast.js'
import RepeatableItemForm from './RepeatableItemForm.vue'

const props = defineProps({
  page:       String,
  section:    String,
  definition: Object,
})

const store     = useUiStore()
const { toast } = useToast()
const items     = ref([])
const expanded  = reactive(new Set())
const showAddForm = ref(false)
const loading   = ref(false)

// Drag-and-drop state
const dragIndex     = ref(null)
const dropTargetIdx = ref(null)

onMounted(async () => {
  loading.value = true
  try {
    const data = await store.fetchSection(props.page, props.section)
    items.value = data.items ?? []

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
  const key = props.definition?.list_field

  const resolveValue = (v) => {
    if (typeof v === 'string' && v.trim() !== '') return v
    if (v && typeof v === 'object' && !Array.isArray(v)) {
      const locale = document.documentElement.lang || 'en'
      if (typeof v[locale] === 'string' && v[locale].trim() !== '') return v[locale]
      return Object.values(v).find(s => typeof s === 'string' && s.trim() !== '') ?? null
    }
    return null
  }

  if (key && key in fields) {
    const resolved = resolveValue(fields[key])
    if (resolved) return resolved
  }

  return Object.values(fields).reduce((found, v) => found ?? resolveValue(v), null) ?? `Item ${idx + 1}`
}

function toggleExpand(key) {
  if (expanded.has(key)) expanded.delete(key)
  else expanded.add(key)
}

async function deleteItem(item, idx) {
  if (!confirm('Delete this item?')) return
  try {
    await store.deleteItem(props.page, props.section, item.id)
    items.value.splice(idx, 1)
    toast({ title: 'Item deleted', variant: 'success' })
  } catch (e) {
    toast({ title: 'Delete failed', description: e.message, variant: 'error' })
  }
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
  dragIndex.value     = idx
  dropTargetIdx.value = idx
  e.dataTransfer.effectAllowed = 'move'
}

function onDragOver(targetIdx) {
  if (dragIndex.value === null) return
  dropTargetIdx.value = targetIdx
}

function onDragLeave() {
  // Keep the last known target — avoids flickering on child element boundaries
}

async function onDragEnd() {
  const from   = dragIndex.value
  const to     = dropTargetIdx.value

  dragIndex.value     = null
  dropTargetIdx.value = null

  if (from === null || to === null || from === to) return

  // Move item in the local array
  const arr = [...items.value]
  const [moved] = arr.splice(from, 1)
  arr.splice(to > from ? to - 1 : to, 0, moved)
  items.value = arr

  const ids = items.value.filter(i => i.id !== null).map(i => i.id)
  if (ids.length < 2) return

  try {
    await store.reorderItems(props.page, props.section, ids)
  } catch (err) {
    toast({ title: 'Reorder failed', description: 'Please try again.', variant: 'error' })
    console.error('[ui-manager] reorderItems failed:', err)
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
