<template>
  <textarea
    :id="id"
    :value="modelValue ?? ''"
    @input="$emit('update:modelValue', $event.target.value)"
    :placeholder="field.props?.placeholder"
    rows="4"
    class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring resize-y min-h-[80px]"
  />
</template>

<script setup>
defineProps({ id: String, field: Object, modelValue: { default: null } })
defineEmits(['update:modelValue'])
</script>
