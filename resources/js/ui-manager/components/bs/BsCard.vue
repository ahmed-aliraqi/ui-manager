<template>
  <div class="card" :class="$attrs.class" v-bind="restAttrs">
    <div v-if="$slots.header || title" class="card-header">
      <slot name="header">{{ title }}</slot>
    </div>
    <div v-if="!noBody" class="card-body">
      <h5 v-if="cardTitle" class="card-title">{{ cardTitle }}</h5>
      <p v-if="subtitle" class="card-subtitle mb-2 text-body-secondary">{{ subtitle }}</p>
      <slot />
    </div>
    <slot v-else />
    <div v-if="$slots.footer" class="card-footer">
      <slot name="footer" />
    </div>
  </div>
</template>

<script setup>
import { useAttrs, computed } from 'vue'

defineOptions({ inheritAttrs: false })

defineProps({
  title: { type: String, default: null },       // card-header text
  cardTitle: { type: String, default: null },   // card-title inside body
  subtitle: { type: String, default: null },
  noBody: { type: Boolean, default: false },
})

const attrs = useAttrs()
const restAttrs = computed(() => {
  const { class: _, ...rest } = attrs
  return rest
})
</script>
