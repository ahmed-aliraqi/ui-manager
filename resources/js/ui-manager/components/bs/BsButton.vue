<template>
  <component
    :is="tag"
    class="btn"
    :class="[
      `btn-${variant}`,
      size && `btn-${size}`,
      { 'w-100': block, disabled: disabled && tag !== 'button' }
    ]"
    :disabled="tag === 'button' ? disabled : undefined"
    :type="tag === 'button' ? type : undefined"
    v-bind="$attrs"
  >
    <span v-if="loading" class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true" />
    <slot />
  </component>
</template>

<script setup>
defineOptions({ inheritAttrs: false })

const props = defineProps({
  variant: { type: String, default: 'primary' },
  size: { type: String, default: null },    // 'sm' | 'lg'
  block: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  loading: { type: Boolean, default: false },
  tag: { type: String, default: 'button' }, // 'button' | 'a' | 'router-link'
  type: { type: String, default: 'button' },
})
</script>
