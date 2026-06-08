<script setup lang="ts">
/**
 * DataTable — wrapper sopra <Table boxed>: container con border + radius +
 * overflow-x-auto. Header dentro al box (bg-muted/50), body con divisori
 * fra righe via DataTableBody.
 *
 * Niente scroll verticale interno né sticky header — le righe crescono
 * naturalmente sotto. Larghezza colonne guidata dal contenuto (`table-auto`):
 * quando la somma supera il container, il box scrolla in orizzontale
 * (`overflow-x-auto`), comportamento atteso su mobile per le tabelle dense.
 *
 * Pagination: usa <DataTablePagination /> FUORI dal componente DataTable
 * (vive nel layout di pagina, non dentro il box).
 */
import type { HTMLAttributes } from 'vue'
import Table from './Table.vue'

defineProps<{ class?: HTMLAttributes['class'] }>()
</script>

<template>
  <Table boxed :class="$props.class">
    <slot />
  </Table>
</template>
