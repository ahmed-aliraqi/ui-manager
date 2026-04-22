<template>
  <div class="bg-background min-h-screen">
    <!-- Fixed sidebar: 256px wide, full viewport height -->
    <AppSidebar />
    <!-- Fixed header: starts at sidebar right edge -->
    <AppHeader />
    <!-- Scrollable content area offset by sidebar + header -->
    <main class="ml-64 pt-14 min-h-screen overflow-y-auto">
      <div class="p-6">
        <RouterView v-slot="{ Component, route }">
          <Transition name="fade" mode="out-in">
            <component :is="Component" :key="route.path" />
          </Transition>
        </RouterView>
      </div>
    </main>
  </div>
</template>

<script setup>
import AppSidebar from './components/layout/AppSidebar.vue'
import AppHeader from './components/layout/AppHeader.vue'
import { useUiStore } from './stores/ui.js'
import { onMounted } from 'vue'

const store = useUiStore()
onMounted(() => store.fetchPages())
</script>

<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.15s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>
