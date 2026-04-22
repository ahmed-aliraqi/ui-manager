<template>
  <div class="space-y-3">
    <!-- Selected icon preview -->
    <div v-if="modelValue" class="flex items-center gap-3 p-3 rounded-lg border bg-muted/30">
      <div
        class="w-10 h-10 text-foreground flex items-center justify-center shrink-0"
        v-html="modelValue"
      />
      <div class="flex-1 min-w-0">
        <p class="text-sm font-medium truncate">{{ selectedName }}</p>
        <p class="text-xs text-muted-foreground">SVG icon</p>
      </div>
      <button
        type="button"
        @click="$emit('update:modelValue', null)"
        class="text-muted-foreground hover:text-destructive transition-colors shrink-0 text-xs"
      >
        Remove
      </button>
    </div>

    <!-- Search + grid -->
    <div>
      <input
        v-model="search"
        type="text"
        placeholder="Search icons…"
        class="w-full h-9 rounded-md border border-input bg-background px-3 py-1 text-sm mb-3 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
      />

      <div v-if="loading" class="text-sm text-muted-foreground py-4 text-center">
        Loading icons…
      </div>

      <div v-else-if="filteredIcons.length === 0" class="text-sm text-muted-foreground py-4 text-center">
        {{ icons.length === 0 ? 'No icons found. Add SVG files to resources/icons/ inside the package.' : 'No matching icons.' }}
      </div>

      <div v-else class="grid grid-cols-6 gap-1 max-h-60 overflow-y-auto p-1 rounded-md border">
        <button
          v-for="icon in filteredIcons"
          :key="icon.name"
          type="button"
          @click="$emit('update:modelValue', icon.content)"
          :title="icon.name"
          :class="[
            'flex items-center justify-center w-full aspect-square rounded-md border transition-colors p-1.5',
            modelValue === icon.content
              ? 'border-primary bg-primary/10 text-primary'
              : 'border-transparent hover:border-input hover:bg-muted text-foreground',
          ]"
        >
          <div class="w-6 h-6" v-html="icon.content" />
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { api } from '../../composables/useApi.js'

const props = defineProps({ id: String, field: Object, modelValue: { default: null } })
defineEmits(['update:modelValue'])

const icons   = ref([])
const loading = ref(false)
const search  = ref('')

onMounted(async () => {
  loading.value = true
  try {
    const { data } = await api.get('/svg-icons')
    icons.value = data.data ?? []
  } catch {
    icons.value = []
  } finally {
    loading.value = false
  }
})

// Find the icon name matching current stored SVG content (for display only)
const selectedName = computed(() => {
  if (!props.modelValue) return ''
  return icons.value.find(i => i.content === props.modelValue)?.name ?? 'icon'
})

const filteredIcons = computed(() => {
  if (!search.value) return icons.value
  const q = search.value.toLowerCase()
  return icons.value.filter(i => i.name.toLowerCase().includes(q))
})
</script>
