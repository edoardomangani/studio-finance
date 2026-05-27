<script setup lang="ts">
/**
 * DataTable — wrapper sopra <Table boxed>: container con border + radius +
 * overflow-x-auto. Header dentro al box (bg-muted/50), body con divisori
 * fra righe via DataTableBody.
 *
 * Niente scroll verticale interno né sticky header — le righe crescono
 * naturalmente sotto. Per tabelle larghe si attiva lo scroll orizzontale
 * passando `class="min-w-[…]"`.
 *
 * Pagination: usa <DataTablePagination /> FUORI dal componente DataTable
 * (vive nel layout di pagina, non dentro il box).
 */
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'
import Table from './Table.vue'

defineProps<{ class?: HTMLAttributes['class'] }>()
</script>

<template>
  <Table
    boxed
    :class="cn('table-fixed', $props.class)"
  >
    <slot />
  </Table>
</template>
