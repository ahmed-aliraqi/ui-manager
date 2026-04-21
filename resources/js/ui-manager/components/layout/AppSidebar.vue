<template>
  <aside class="w-64 min-h-screen bg-slate-900 text-slate-100 flex flex-col shrink-0">
    <!-- Logo / Title -->
    <div class="p-5 border-b border-slate-700">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 bg-blue-500 rounded-lg flex items-center justify-center text-white font-bold text-sm">
          UI
        </div>
        <span class="font-semibold text-lg">{{ config.title }}</span>
      </div>
    </div>

    <!-- Home button (optional) -->
    <div v-if="config.homeButton?.display" class="p-3 border-b border-slate-700">
      <a
        :href="config.homeButton.uri"
        class="flex items-center gap-2 px-3 py-2 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-white transition-colors text-sm"
      >
        <HomeIcon class="w-4 h-4" />
        {{ config.homeButton.label }}
      </a>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 p-3 overflow-y-auto">
      <p class="text-xs font-medium text-slate-400 uppercase tracking-wider mb-2 px-3">Pages</p>

      <div v-if="store.loading" class="space-y-1">
        <div v-for="i in 4" :key="i" class="h-9 bg-slate-800 rounded-lg animate-pulse" />
      </div>

      <ul v-else class="space-y-1">
        <li v-for="page in store.pages" :key="page.name">
          <RouterLink
            :to="{ name: 'page-show', params: { page: page.name } }"
            class="flex items-center gap-2 px-3 py-2 rounded-lg text-slate-300 hover:bg-slate-800 hover:text-white transition-colors text-sm group"
            active-class="bg-slate-800 text-white"
          >
            <LayoutIcon class="w-4 h-4 shrink-0 text-slate-400 group-hover:text-slate-200" />
            <span class="truncate">{{ page.display_name }}</span>
            <span class="ml-auto text-xs text-slate-500">{{ page.sections?.length || 0 }}</span>
          </RouterLink>
        </li>
      </ul>
    </nav>

    <!-- Footer -->
    <div class="p-4 border-t border-slate-700">
      <p class="text-xs text-slate-500 text-center">
        UI Manager
      </p>
    </div>
  </aside>
</template>

<script setup>
import { HomeIcon, LayoutIcon } from 'lucide-vue-next'
import { useUiStore } from '../../stores/ui.js'

const store = useUiStore()
const config = window.__UI_MANAGER_CONFIG__ || {}
</script>
