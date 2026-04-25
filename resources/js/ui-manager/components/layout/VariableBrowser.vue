<template>
  <Teleport to="body">
    <Transition name="overlay">
      <div
        v-if="open"
        class="fixed inset-0 z-[90] bg-black/40"
        @click.self="close"
      />
    </Transition>

    <Transition name="panel">
      <aside
        v-if="open"
        class="fixed top-0 right-0 bottom-0 z-[91] w-80 bg-background border-l shadow-xl flex flex-col"
        role="complementary"
        aria-label="Variable Browser"
      >
        <!-- Header -->
        <div class="flex items-center justify-between px-4 py-3 border-b">
          <h2 class="text-sm font-semibold">Variable Browser</h2>
          <button
            type="button"
            @click="close"
            class="text-muted-foreground hover:text-foreground transition-colors"
            aria-label="Close variable browser"
          >
            <XIcon class="w-4 h-4" />
          </button>
        </div>

        <!-- Search -->
        <div class="px-4 py-3 border-b">
          <div class="relative">
            <SearchIcon class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-muted-foreground pointer-events-none" />
            <input
              ref="searchInput"
              v-model="query"
              type="search"
              placeholder="Search variables…"
              class="w-full pl-8 pr-3 py-1.5 text-sm rounded-md border bg-background focus:outline-none focus:ring-2 focus:ring-ring"
            />
          </div>
        </div>

        <!-- Variable list -->
        <div class="flex-1 overflow-y-auto">
          <div v-if="filtered.length" class="divide-y">
            <button
              v-for="v in filtered"
              :key="v.key"
              type="button"
              @click="copy(v.placeholder)"
              class="w-full flex items-start gap-3 px-4 py-3 hover:bg-accent transition-colors text-left group"
              :title="`Click to copy ${v.placeholder}`"
            >
              <code class="text-primary font-mono text-xs leading-5 shrink-0">{{ v.placeholder }}</code>
              <span class="text-xs text-muted-foreground truncate leading-5">{{ v.key }}</span>
              <span
                class="ml-auto text-xs text-muted-foreground opacity-0 group-hover:opacity-100 transition-opacity shrink-0 leading-5"
              >Copy</span>
            </button>
          </div>

          <div v-else class="px-4 py-8 text-center text-muted-foreground">
            <CodeIcon class="w-6 h-6 mx-auto mb-2 opacity-40" />
            <p class="text-sm">{{ query ? 'No variables match your search.' : 'No variables registered.' }}</p>
          </div>
        </div>

        <!-- Copied confirmation -->
        <Transition name="toast">
          <div
            v-if="copiedKey"
            class="absolute bottom-4 left-4 right-4 bg-green-50 border border-green-200 text-green-800 rounded-lg px-3 py-2 text-xs flex items-center gap-2 shadow"
          >
            <CheckCircleIcon class="w-3.5 h-3.5 text-green-600 shrink-0" />
            Copied <code class="font-mono">{{ copiedKey }}</code>
          </div>
        </Transition>
      </aside>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch, nextTick } from 'vue'
import { XIcon, SearchIcon, CodeIcon, CheckCircleIcon } from 'lucide-vue-next'
import { useUiStore } from '../../stores/ui.js'

const props  = defineProps({ open: Boolean })
const emit   = defineEmits(['close'])

const store      = useUiStore()
const query      = ref('')
const copiedKey  = ref(null)
const searchInput = ref(null)

const filtered = computed(() => {
  if (!query.value) return store.variables
  const q = query.value.toLowerCase()
  return store.variables.filter(v => v.key.toLowerCase().includes(q))
})

watch(() => props.open, async (isOpen) => {
  if (isOpen) {
    query.value    = ''
    copiedKey.value = null
    await nextTick()
    searchInput.value?.focus()

    if (!store.variables.length) {
      store.fetchVariables()
    }
  }
})

function close() {
  emit('close')
}

function copy(placeholder) {
  navigator.clipboard.writeText(placeholder).then(() => {
    copiedKey.value = placeholder
    setTimeout(() => { copiedKey.value = null }, 2000)
  }).catch(() => {})
}
</script>

<style scoped>
.overlay-enter-active, .overlay-leave-active { transition: opacity 0.2s ease; }
.overlay-enter-from, .overlay-leave-to { opacity: 0; }

.panel-enter-active, .panel-leave-active { transition: transform 0.25s ease; }
.panel-enter-from, .panel-leave-to { transform: translateX(100%); }

.toast-enter-active, .toast-leave-active { transition: all 0.2s ease; }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translateY(0.5rem); }
</style>
