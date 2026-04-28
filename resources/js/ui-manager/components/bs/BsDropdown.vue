<template>
  <div class="dropdown" :class="{ dropup, dropend, dropstart }" v-click-outside="close">
    <!-- Toggle -->
    <button
      class="btn dropdown-toggle"
      :class="[`btn-${variant}`, size && `btn-${size}`]"
      type="button"
      :aria-expanded="open"
      @click="toggle"
    >
      <slot name="toggle">{{ label }}</slot>
    </button>

    <!-- Menu -->
    <Transition name="uim-dropdown">
      <ul v-if="open" class="dropdown-menu show" :class="{ 'dropdown-menu-end': align === 'end' }">
        <slot />
      </ul>
    </Transition>
  </div>
</template>

<script setup>
import { ref } from 'vue'

defineProps({
  label: { type: String, default: 'Actions' },
  variant: { type: String, default: 'secondary' },
  size: { type: String, default: null },
  align: { type: String, default: 'start' },  // 'start' | 'end'
  dropup: { type: Boolean, default: false },
  dropend: { type: Boolean, default: false },
  dropstart: { type: Boolean, default: false },
})

const open = ref(false)
const toggle = () => { open.value = !open.value }
const close = () => { open.value = false }

// Simple click-outside directive
const vClickOutside = {
  mounted(el, binding) {
    el.__clickOutside = (e) => { if (!el.contains(e.target)) binding.value() }
    document.addEventListener('click', el.__clickOutside)
  },
  unmounted(el) {
    document.removeEventListener('click', el.__clickOutside)
  },
}
</script>

<style scoped>
.uim-dropdown-enter-active,
.uim-dropdown-leave-active {
  transition: opacity 0.1s ease, transform 0.1s ease;
}
.uim-dropdown-enter-from,
.uim-dropdown-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}
</style>
