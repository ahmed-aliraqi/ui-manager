<template>
  <div class="uim-input-group">
    <!-- Currency badge on left if set -->
    <span v-if="activeCurrency && !showCurrencySelect" class="uim-input-group-text">{{ activeCurrency }}</span>

    <!-- Amount input -->
    <input
      :id="id"
      type="number"
      :value="amount"
      :min="0"
      :step="stepValue"
      @input="onAmountChange($event.target.value)"
      placeholder="0.00"
      class="uim-form-control"
    />

    <!-- Currency selector (shown when multiple currencies or no fixed currency) -->
    <select
      v-if="showCurrencySelect"
      :value="currency"
      @change="onCurrencyChange($event.target.value)"
      class="uim-form-select"
      style="max-width:90px"
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
