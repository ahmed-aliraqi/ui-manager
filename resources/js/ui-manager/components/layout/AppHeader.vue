<template>
  <header class="fixed top-0 left-64 right-0 h-14 z-30 border-b bg-background flex items-center px-6 gap-4">
    <!-- Breadcrumb -->
    <nav class="flex items-center gap-1.5 text-sm text-muted-foreground">
      <RouterLink to="/" class="hover:text-foreground transition-colors">UI Manager</RouterLink>
      <template v-for="(crumb, idx) in breadcrumbs" :key="idx">
        <ChevronRightIcon class="w-3.5 h-3.5" />
        <span
          v-if="idx === breadcrumbs.length - 1"
          class="text-foreground font-medium"
        >{{ crumb.label }}</span>
        <RouterLink
          v-else
          :to="crumb.to"
          class="hover:text-foreground transition-colors"
        >{{ crumb.label }}</RouterLink>
      </template>
    </nav>

    <div class="ml-auto flex items-center gap-2">
      <!-- Toast area placeholder -->
    </div>
  </header>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { ChevronRightIcon } from 'lucide-vue-next'
import { useUiStore } from '../../stores/ui.js'

const route = useRoute()
const store = useUiStore()

const breadcrumbs = computed(() => {
  const crumbs = []

  if (route.params.page) {
    const page = store.pageMap[route.params.page]
    crumbs.push({
      label: page?.display_name || route.params.page,
      to: { name: 'page-show', params: { page: route.params.page } },
    })
  }

  if (route.params.section) {
    const page = store.pageMap[route.params.page]
    const section = page?.sections?.find(s => s.name === route.params.section)
    crumbs.push({
      label: section?.label || route.params.section,
      to: route.path,
    })
  }

  return crumbs
})
</script>
