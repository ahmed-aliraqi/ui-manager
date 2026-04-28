<template>
  <div :class="{ 'table-responsive': responsive }">
    <table
      class="table"
      :class="[
        variant && `table-${variant}`,
        { 'table-striped': striped, 'table-hover': hover, 'table-bordered': bordered, 'table-sm': small }
      ]"
    >
      <thead v-if="$slots.head || columns.length">
        <slot name="head">
          <tr>
            <th v-for="col in columns" :key="col.key ?? col" scope="col">
              {{ col.label ?? col }}
            </th>
          </tr>
        </slot>
      </thead>
      <tbody>
        <slot />
      </tbody>
      <tfoot v-if="$slots.foot">
        <slot name="foot" />
      </tfoot>
    </table>
  </div>
</template>

<script setup>
defineProps({
  columns: { type: Array, default: () => [] },  // [{key, label}] or ['string']
  variant: { type: String, default: null },
  striped: { type: Boolean, default: false },
  hover: { type: Boolean, default: false },
  bordered: { type: Boolean, default: false },
  small: { type: Boolean, default: false },
  responsive: { type: Boolean, default: true },
})
</script>
