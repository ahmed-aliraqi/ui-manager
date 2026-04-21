<template>
  <div v-if="sectionDef">
    <div class="mb-6 flex items-center justify-between">
      <div>
        <RouterLink
          :to="{ name: 'page-show', params: { page } }"
          class="text-sm text-muted-foreground hover:text-foreground flex items-center gap-1 mb-1"
        >
          <ChevronLeftIcon class="w-3.5 h-3.5" /> Back
        </RouterLink>
        <h1 class="text-2xl font-semibold">{{ sectionDef.label }}</h1>
        <p class="text-muted-foreground text-sm mt-1">
          {{ sectionDef.repeatable ? 'Repeatable section — manage list items below.' : 'Edit section fields.' }}
        </p>
      </div>
    </div>

    <!-- Non-repeatable form -->
    <SectionForm
      v-if="!sectionDef.repeatable"
      :page="page"
      :section="section"
      :definition="sectionDef"
    />

    <!-- Repeatable list -->
    <RepeatableSection
      v-else
      :page="page"
      :section="section"
      :definition="sectionDef"
    />
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { ChevronLeftIcon } from 'lucide-vue-next'
import { useUiStore } from '../stores/ui.js'
import SectionForm from '../components/SectionForm.vue'
import RepeatableSection from '../components/repeatable/RepeatableSection.vue'

const props = defineProps({ page: String, section: String })
const store = useUiStore()

const pageData = computed(() => store.pageMap[props.page])
const sectionDef = computed(() => pageData.value?.sections?.find(s => s.name === props.section))

onMounted(() => store.fetchVariables())
</script>
