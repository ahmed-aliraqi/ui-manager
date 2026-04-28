<template>
  <Teleport to="body">
    <div class="uim-toast-region" aria-live="polite" aria-label="Notifications">
      <TransitionGroup name="uim-toast">
        <div
          v-for="t in toasts"
          :key="t.id"
          role="alert"
          class="uim-toast"
          :class="`uim-toast--${t.variant || 'default'}`"
        >
          <CheckCircleIcon v-if="t.variant === 'success'" class="uim-toast__icon uim-toast__icon--success" />
          <XCircleIcon    v-else-if="t.variant === 'error'"   class="uim-toast__icon uim-toast__icon--error" />
          <AlertCircleIcon v-else-if="t.variant === 'warning'" class="uim-toast__icon uim-toast__icon--warning" />
          <InfoIcon v-else class="uim-toast__icon" />

          <div class="uim-toast__body">
            <p v-if="t.title" class="uim-toast__title">{{ t.title }}</p>
            <p v-if="t.description" class="uim-toast__desc">{{ t.description }}</p>
          </div>

          <button
            type="button"
            @click="dismiss(t.id)"
            class="uim-toast__close"
            aria-label="Dismiss"
          >
            <XIcon style="width:.875rem;height:.875rem;" />
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
.uim-toast-region {
  position: fixed;
  bottom: 1rem;
  right: 1rem;
  z-index: 9999;
  display: flex;
  flex-direction: column;
  gap: .5rem;
  pointer-events: none;
  max-width: 24rem;
  width: 100%;
}

.uim-toast {
  pointer-events: auto;
  display: flex;
  align-items: flex-start;
  gap: .75rem;
  padding: .75rem 1rem;
  border-radius: .5rem;
  border: 1px solid #e2e8f0;
  background: #fff;
  color: #1e293b;
  box-shadow: 0 4px 12px rgba(0,0,0,.12);
  font-family: inherit;
  font-size: .875rem;
}

.uim-toast--success { background: #f0fdf4; border-color: #bbf7d0; color: #14532d; }
.uim-toast--error   { background: #fef2f2; border-color: #fecaca; color: #7f1d1d; }
.uim-toast--warning { background: #fffbeb; border-color: #fde68a; color: #78350f; }

.uim-toast__icon {
  width: 1rem;
  height: 1rem;
  flex-shrink: 0;
  margin-top: .125rem;
  color: #94a3b8;
}
.uim-toast__icon--success { color: #16a34a; }
.uim-toast__icon--error   { color: #dc2626; }
.uim-toast__icon--warning { color: #d97706; }

.uim-toast__body { flex: 1; min-width: 0; }

.uim-toast__title {
  margin: 0 0 .125rem;
  font-weight: 500;
  line-height: 1.25;
}

.uim-toast__desc {
  margin: 0;
  font-size: .75rem;
  opacity: .75;
  line-height: 1.4;
}

.uim-toast__close {
  flex-shrink: 0;
  background: none;
  border: none;
  padding: 0;
  cursor: pointer;
  color: inherit;
  opacity: .5;
  display: flex;
  align-items: center;
  margin-right: -.25rem;
}
.uim-toast__close:hover { opacity: 1; }

/* Transitions */
.uim-toast-enter-active,
.uim-toast-leave-active { transition: all 0.25s ease; }
.uim-toast-enter-from,
.uim-toast-leave-to { opacity: 0; transform: translateX(1rem); }
.uim-toast-move { transition: transform 0.25s ease; }
</style>
