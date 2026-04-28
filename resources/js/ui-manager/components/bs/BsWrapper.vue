<template>
  <div class="uim-bs" :class="[$attrs.class]" v-bind="restAttrs">
    <slot />
  </div>
</template>

<script setup>
import { useAttrs, computed } from 'vue'

defineOptions({ inheritAttrs: false })

const attrs = useAttrs()

const restAttrs = computed(() => {
  const { class: _, ...rest } = attrs
  return rest
})
</script>
