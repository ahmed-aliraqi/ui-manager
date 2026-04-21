<template>
  <div class="min-h-screen flex bg-background">
    <AppSidebar />
    <div class="flex-1 flex flex-col min-w-0">
      <AppHeader />
      <main class="flex-1 p-6 overflow-auto">
        <RouterView v-slot="{ Component, route }">
          <Transition
            name="fade"
            mode="out-in"
          >
            <component :is="Component" :key="route.path" />
          </Transition>
        </RouterView>
      </main>
    </div>
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
