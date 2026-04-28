<template>
  <!-- Backdrop -->
  <Teleport to="#ui-manager-app">
    <Transition name="uim-modal-fade">
      <div v-if="modelValue" class="uim-bs-backdrop" @click="closeOnBackdrop && close()" />
    </Transition>

    <!-- Modal -->
    <Transition name="uim-modal-slide">
      <div
        v-if="modelValue"
        class="uim-bs"
        style="position: fixed; inset: 0; z-index: 1055; overflow-y: auto; display: flex; align-items: center; justify-content: center; padding: 1rem;"
        @click.self="closeOnBackdrop && close()"
      >
        <div class="modal-dialog" :class="[sizeClass, { 'modal-dialog-scrollable': scrollable, 'modal-dialog-centered': centered }]" style="position: relative; width: 100%; margin: 0;">
          <div class="modal-content">
            <div v-if="!noHeader" class="modal-header">
              <h5 class="modal-title">
                <slot name="title">{{ title }}</slot>
              </h5>
              <button v-if="!noClose" type="button" class="btn-close" @click="close" />
            </div>
            <div class="modal-body">
              <slot />
            </div>
            <div v-if="$slots.footer" class="modal-footer">
              <slot name="footer" />
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed, watch, onUnmounted } from 'vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  title: { type: String, default: '' },
  size: { type: String, default: null },   // 'sm' | 'lg' | 'xl'
  centered: { type: Boolean, default: true },
  scrollable: { type: Boolean, default: false },
  noHeader: { type: Boolean, default: false },
  noClose: { type: Boolean, default: false },
  closeOnBackdrop: { type: Boolean, default: true },
})

const emit = defineEmits(['update:modelValue', 'hide', 'show'])

const sizeClass = computed(() => props.size ? `modal-${props.size}` : null)

function close() {
  emit('update:modelValue', false)
  emit('hide')
}

watch(() => props.modelValue, (val) => {
  if (val) {
    emit('show')
    document.body.style.overflow = 'hidden'
  } else {
    document.body.style.overflow = ''
  }
})

onUnmounted(() => {
  document.body.style.overflow = ''
})
</script>

<style scoped>
.uim-modal-fade-enter-active,
.uim-modal-fade-leave-active {
  transition: opacity 0.2s ease;
}
.uim-modal-fade-enter-from,
.uim-modal-fade-leave-to {
  opacity: 0;
}

.uim-modal-slide-enter-active,
.uim-modal-slide-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.uim-modal-slide-enter-from,
.uim-modal-slide-leave-to {
  opacity: 0;
  transform: translateY(-20px);
}
</style>
