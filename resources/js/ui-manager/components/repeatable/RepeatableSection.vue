<template>
  <div>
    <!-- Items list -->
    <div v-if="items.length" class="mb-3">
      <template v-for="(item, idx) in items" :key="itemKey(item, idx)">
        <!-- Drop indicator above -->
        <div
          v-if="dropTargetIdx === idx && dragIndex !== null && dragIndex !== idx"
          class="my-1"
          style="height:2px;border-radius:2px;background:var(--bs-primary,#0d6efd)"
        />

        <div
          draggable="true"
          @dragstart="onDragStart(idx, $event)"
          @dragover.prevent="onDragOver(idx)"
          @dragleave="onDragLeave"
          @dragend="onDragEnd"
          class="card mb-2"
          :style="dragIndex === idx ? 'opacity:.3' : ''"
        >
          <!-- Card header -->
          <div class="card-header d-flex align-items-center gap-2 py-2 px-3">
            <GripVerticalIcon
              class="text-muted"
              style="width:1rem;height:1rem;cursor:grab;flex-shrink:0"
            />
            <span class="small fw-medium flex-grow-1 text-truncate">
              {{ itemLabel(item, idx) }}
            </span>
            <span
              v-if="item.id === null"
              class="badge text-bg-warning me-1"
              style="font-size:.65rem"
            >default</span>
            <button
              type="button"
              @click="toggleExpand(itemKey(item, idx))"
              class="btn btn-link btn-sm p-0 text-muted"
              :aria-expanded="expanded.has(itemKey(item, idx))"
              :aria-label="expanded.has(itemKey(item, idx)) ? 'Collapse' : 'Expand'"
            >
              <ChevronDownIcon
                style="width:1rem;height:1rem;transition:transform .15s"
                :style="expanded.has(itemKey(item, idx)) ? 'transform:rotate(180deg)' : ''"
              />
            </button>
            <button
              v-if="item.id !== null"
              type="button"
              @click="deleteItem(item, idx)"
              class="btn btn-link btn-sm p-0 text-muted"
              aria-label="Delete item"
            >
              <Trash2Icon style="width:1rem;height:1rem;" />
            </button>
          </div>

          <Transition name="slide">
            <div v-if="expanded.has(itemKey(item, idx))" class="card-body p-3">
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

      <!-- Drop indicator at end -->
      <div
        v-if="dropTargetIdx === items.length && dragIndex !== null"
        class="my-1"
        style="height:2px;border-radius:2px;background:var(--bs-primary,#0d6efd)"
      />
    </div>

    <!-- Empty state -->
    <div v-else class="border border-dashed rounded p-5 text-center text-muted mb-3">
      <ListIcon class="mb-2" style="width:1.5rem;height:1.5rem;opacity:.4" />
      <p class="small mb-0">No items yet — add your first one below.</p>
    </div>

    <!-- Add new item -->
    <button
      type="button"
      @click="showAddForm = !showAddForm"
      class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-2"
    >
      <PlusIcon style="width:1rem;height:1rem;" />
      Add item
    </button>

    <Transition name="slide">
      <div v-if="showAddForm" class="card mt-3">
        <div class="card-body p-3">
          <p class="small fw-medium mb-3">New item</p>
          <RepeatableItemForm
            :definition="definition"
            :item="null"
            :page="page"
            :section="section"
            @saved="onNewItemSaved"
            @cancel="showAddForm = false"
          />
        </div>
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
  // Intentionally empty — keeps last known target to avoid flicker
}

async function onDragEnd() {
  const from = dragIndex.value
  const to   = dropTargetIdx.value

  dragIndex.value     = null
  dropTargetIdx.value = null

  if (from === null || to === null || from === to) return

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
