<template>
  <div class="flex items-center gap-2">
    <!-- Amount input -->
    <div class="relative flex-1">
      <span
        v-if="activeCurrency"
        class="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground text-sm pointer-events-none select-none"
      >{{ activeCurrency }}</span>
      <input
        :id="id"
        type="number"
        :value="amount"
        :min="0"
        :step="stepValue"
        @input="onAmountChange($event.target.value)"
        placeholder="0.00"
        class="w-full h-9 rounded-md border border-input bg-background py-1 pr-3 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
        :class="activeCurrency ? 'pl-10' : 'pl-3'"
      />
    </div>

    <!-- Currency selector (shown when multiple currencies or no fixed currency) -->
    <select
      v-if="showCurrencySelect"
      :value="currency"
      @change="onCurrencyChange($event.target.value)"
      class="h-9 rounded-md border border-input bg-background px-2 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
    >
      <option v-for="c in currencyOptions" :key="c" :value="c">{{ c }}</option>
    </select>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({ id: String, field: Object, modelValue: { default: null } })
const emit  = defineEmits(['update:modelValue'])

const amount   = computed(() => props.modelValue?.amount   ?? '')
const currency = computed(() => props.modelValue?.currency ?? props.field.currency ?? '')

const activeCurrency = computed(() => currency.value || null)

const currencyOptions = computed(() =>
  props.field.currencies?.length
    ? props.field.currencies
    : props.field.currency ? [props.field.currency] : []
)

const showCurrencySelect = computed(() =>
  (props.field.currencies?.length > 1) ||
  (!props.field.currency && !props.field.currencies?.length)
)

const stepValue = computed(() => {
  const decimals = props.field.decimals ?? 2
  return decimals > 0 ? (1 / Math.pow(10, decimals)).toFixed(decimals) : '1'
})

function onAmountChange(value) {
  const parsed = value === '' ? null : parseFloat(value)
  emit('update:modelValue', {
    amount:   isNaN(parsed) ? null : parsed,
    currency: currency.value || null,
  })
}

function onCurrencyChange(value) {
  emit('update:modelValue', {
    amount:   amount.value === '' ? null : parseFloat(amount.value),
    currency: value || null,
  })
}
</script>
