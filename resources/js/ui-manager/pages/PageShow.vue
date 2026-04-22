<template>
  <div v-if="page">
    <div class="mb-6">
      <h1 class="text-2xl font-semibold">{{ page.display_name }}</h1>
      <p class="text-muted-foreground text-sm mt-1">
        {{ visibleSections.length }} {{ visibleSections.length === 1 ? 'section' : 'sections' }}
      </p>
    </div>

    <div v-if="visibleSections.length" class="border rounded-xl overflow-hidden">
      <!-- Section tabs -->
      <div class="flex border-b bg-muted/40 overflow-x-auto">
        <button
          v-for="section in visibleSections"
          :key="section.name"
          @click="activeSection = section.name"
          class="px-5 py-3 text-sm font-medium whitespace-nowrap border-b-2 transition-colors"
          :class="activeSection === section.name
            ? 'border-primary text-primary bg-background'
            : 'border-transparent text-muted-foreground hover:text-foreground'"
        >
          {{ section.label }}
          <span
            v-if="section.repeatable"
            class="ml-1.5 text-xs bg-primary/10 text-primary rounded-full px-1.5 py-0.5"
          >list</span>
        </button>
      </div>

      <!-- Inline edit form — no preview, no redirect -->
      <div v-if="activeSectionDef" class="p-6">
        <SectionForm
          v-if="!activeSectionDef.repeatable"
          :key="activeSectionDef.name"
          :page="page.name"
          :section="activeSectionDef.name"
          :definition="activeSectionDef"
        />
        <RepeatableSection
          v-else
          :key="activeSectionDef.name"
          :page="page.name"
          :section="activeSectionDef.name"
          :definition="activeSectionDef"
        />
      </div>
    </div>

    <div v-else class="rounded-xl border border-dashed p-12 text-center text-muted-foreground">
      <LayoutIcon class="w-8 h-8 mx-auto mb-3 opacity-40" />
      <p class="text-sm">No sections registered for this page.</p>
    </div>
  </div>

  <div v-else class="flex items-center justify-center h-64 text-muted-foreground">
    Page not found.
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { LayoutIcon } from 'lucide-vue-next'
import { useUiStore } from '../stores/ui.js'
import SectionForm from '../components/SectionForm.vue'
import RepeatableSection from '../components/repeatable/RepeatableSection.vue'

const props = defineProps({ page: String })
const store = useUiStore()

const page = computed(() => store.pageMap[props.page])
const visibleSections = computed(() => page.value?.sections?.filter(s => s.visible) ?? [])
const activeSection = ref(null)

watch(visibleSections, (sections) => {
  if (sections.length && !activeSection.value) {
    activeSection.value = sections[0].name
  }
}, { immediate: true })

const activeSectionDef = computed(() =>
  visibleSections.value.find(s => s.name === activeSection.value) ?? null
)
</script>
