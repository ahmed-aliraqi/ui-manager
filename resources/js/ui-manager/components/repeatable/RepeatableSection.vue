<template>
  <div>
    <!-- Items list -->
    <div
      v-if="items.length"
      ref="listRef"
      class="space-y-3 mb-4"
    >
      <div
        v-for="(item, idx) in items"
        :key="item.id || idx"
        class="rounded-xl border bg-card overflow-hidden"
      >
        <div class="flex items-center gap-2 px-4 py-3 bg-muted/30 border-b">
          <GripVerticalIcon class="w-4 h-4 text-muted-foreground cursor-grab" />
          <span class="text-sm font-medium flex-1">Item {{ idx + 1 }}</span>
          <button
            type="button"
            @click="toggleExpand(item.id || idx)"
            class="text-muted-foreground hover:text-foreground"
          >
            <ChevronDownIcon
              class="w-4 h-4 transition-transform"
              :class="{ 'rotate-180': expanded.has(item.id || idx) }"
            />
          </button>
          <button
            type="button"
            @click="deleteItem(item, idx)"
            class="text-muted-foreground hover:text-destructive transition-colors"
          >
            <Trash2Icon class="w-4 h-4" />
          </button>
        </div>

        <Transition name="slide">
          <div v-if="expanded.has(item.id || idx)" class="p-4 space-y-4">
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
        <h3 class="text-sm font-medium mb-4">New item</h3>
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
  PlusIcon, ListIcon
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

onMounted(async () => {
  try {
    const data = await store.fetchSection(props.page, props.section)
    items.value = data.items || []
  } catch {}
})

function toggleExpand(id) {
  if (expanded.has(id)) expanded.delete(id)
  else expanded.add(id)
}

async function deleteItem(item, idx) {
  if (!confirm('Delete this item?')) return
  await store.deleteItem(props.page, props.section, item.id)
  items.value.splice(idx, 1)
}

function onItemSaved(idx, updated) {
  items.value[idx] = { ...items.value[idx], ...updated }
}

function onNewItemSaved(newItem) {
  items.value.push(newItem)
  showAddForm.value = false
}
</script>

<style scoped>
.slide-enter-active, .slide-leave-active {
  transition: all 0.2s ease;
  overflow: hidden;
}
.slide-enter-from, .slide-leave-to {
  max-height: 0;
  opacity: 0;
}
.slide-enter-to, .slide-leave-from {
  max-height: 1000px;
  opacity: 1;
}
</style>
