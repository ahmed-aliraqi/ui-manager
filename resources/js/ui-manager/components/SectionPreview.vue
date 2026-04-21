<template>
  <div v-if="sectionData" class="space-y-3">
    <div
      v-for="field in section.fields"
      :key="field.name"
      class="flex gap-2 text-sm"
    >
      <span class="font-medium text-muted-foreground w-32 shrink-0">{{ field.label }}</span>
      <span class="text-foreground truncate max-w-xs">
        <template v-if="field.type === 'image' && sectionData[field.name]">
          <img :src="sectionData[field.name]?.url || sectionData[field.name]" class="h-8 w-8 object-cover rounded" />
        </template>
        <template v-else>
          {{ preview(sectionData[field.name]) }}
        </template>
      </span>
    </div>
  </div>
  <div v-else class="text-sm text-muted-foreground italic">No data saved yet — defaults will be used.</div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useUiStore } from '../stores/ui.js'

const props = defineProps({ page: String, section: Object })
const store = useUiStore()
const sectionData = ref(null)

onMounted(async () => {
  try {
    const data = await store.fetchSection(props.page, props.section.name)
    sectionData.value = data.fields || {}
  } catch {}
})

function preview(val) {
  if (val === null || val === undefined) return '—'
  if (typeof val === 'object') return JSON.stringify(val).slice(0, 60) + '…'
  const str = String(val)
  return str.length > 80 ? str.slice(0, 80) + '…' : str
}
</script>
