<template>
  <Transition name="uim-bs-fade">
    <div
      v-if="visible"
      class="alert"
      :class="[`alert-${variant}`, { 'alert-dismissible': dismissible }]"
      role="alert"
    >
      <slot />
      <button
        v-if="dismissible"
        type="button"
        class="btn-close"
        aria-label="Close"
        @click="visible = false"
      />
    </div>
  </Transition>
</template>

<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  variant: { type: String, default: 'info' },
  dismissible: { type: Boolean, default: false },
  modelValue: { type: Boolean, default: true },
})

const emit = defineEmits(['update:modelValue'])

const visible = ref(props.modelValue)

watch(() => props.modelValue, (v) => { visible.value = v })
watch(visible, (v) => { emit('update:modelValue', v) })
</script>

<style scoped>
.uim-bs-fade-enter-active,
.uim-bs-fade-leave-active {
  transition: opacity 0.15s ease;
}
.uim-bs-fade-enter-from,
.uim-bs-fade-leave-to {
  opacity: 0;
}
</style>
