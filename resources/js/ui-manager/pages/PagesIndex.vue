<template>
  <div>
    <div class="mb-6">
      <h1 class="text-2xl font-semibold">Pages</h1>
      <p class="text-muted-foreground text-sm mt-1">Select a page to manage its UI sections.</p>
    </div>

    <div v-if="store.loading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <div v-for="i in 6" :key="i" class="h-32 rounded-xl bg-muted animate-pulse" />
    </div>

    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <RouterLink
        v-for="page in store.pages"
        :key="page.name"
        :to="{ name: 'page-show', params: { page: page.name } }"
        class="group block rounded-xl border bg-card p-5 hover:border-primary hover:shadow-sm transition-all"
      >
        <div class="flex items-start justify-between mb-3">
          <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center">
            <LayoutIcon class="w-5 h-5 text-primary" />
          </div>
          <ChevronRightIcon class="w-4 h-4 text-muted-foreground group-hover:text-primary transition-colors mt-1" />
        </div>
        <h3 class="font-medium text-foreground">{{ page.display_name }}</h3>
        <p class="text-sm text-muted-foreground mt-1">
          {{ page.sections?.length || 0 }} {{ page.sections?.length === 1 ? 'section' : 'sections' }}
        </p>
      </RouterLink>
    </div>
  </div>
</template>

<script setup>
import { LayoutIcon, ChevronRightIcon } from 'lucide-vue-next'
import { useUiStore } from '../stores/ui.js'

const store = useUiStore()
</script>
