<template>
  <Teleport to="body">
    <div
      aria-live="polite"
      aria-label="Notifications"
      class="fixed bottom-4 right-4 z-[9999] flex flex-col gap-2 pointer-events-none"
    >
      <TransitionGroup name="toast">
        <div
          v-for="t in toasts"
          :key="t.id"
          role="alert"
          class="pointer-events-auto flex items-start gap-3 rounded-lg border px-4 py-3 shadow-lg max-w-sm w-full"
          :class="{
            'bg-green-50 border-green-200 text-green-900': t.variant === 'success',
            'bg-red-50 border-red-200 text-red-900': t.variant === 'error',
            'bg-yellow-50 border-yellow-200 text-yellow-900': t.variant === 'warning',
            'bg-background border text-foreground': !['success','error','warning'].includes(t.variant),
          }"
        >
          <CheckCircleIcon v-if="t.variant === 'success'" class="w-4 h-4 shrink-0 mt-0.5 text-green-600" />
          <XCircleIcon    v-else-if="t.variant === 'error'"   class="w-4 h-4 shrink-0 mt-0.5 text-red-600" />
          <AlertCircleIcon v-else-if="t.variant === 'warning'" class="w-4 h-4 shrink-0 mt-0.5 text-yellow-600" />
          <InfoIcon v-else class="w-4 h-4 shrink-0 mt-0.5 text-muted-foreground" />

          <div class="flex-1 min-w-0">
            <p v-if="t.title" class="text-sm font-medium leading-tight">{{ t.title }}</p>
            <p v-if="t.description" class="text-xs text-muted-foreground mt-0.5">{{ t.description }}</p>
          </div>

          <button
            type="button"
            @click="dismiss(t.id)"
            class="shrink-0 text-muted-foreground hover:text-foreground transition-colors -mr-1"
            aria-label="Dismiss"
          >
            <XIcon class="w-3.5 h-3.5" />
          </button>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<script setup>
import { CheckCircleIcon, XCircleIcon, AlertCircleIcon, InfoIcon, XIcon } from 'lucide-vue-next'
import { useToast } from '../../composables/useToast.js'

const { toasts, dismiss } = useToast()
</script>

<style scoped>
.toast-enter-active,
.toast-leave-active {
  transition: all 0.25s ease;
}
.toast-enter-from,
.toast-leave-to {
  opacity: 0;
  transform: translateX(1rem);
}
.toast-move {
  transition: transform 0.25s ease;
}
</style>
