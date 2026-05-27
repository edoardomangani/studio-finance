<script setup lang="ts">
/**
 * DataTableBody — wrapper di TableBody con hover/selected pre-applicati.
 *
 * Il radius + border perimetrale arriva dal container <Table boxed>, non
 * dalle celle. Le sticky columns (opt-in via classi su TableCell) hanno bg-card
 * applicato automaticamente qui sotto.
 *
 * Slot default: `<DataTableRow>` ×N.
 */
import { cn } from '@/lib/utils'
import TableBody from './TableBody.vue'
</script>

<template>
  <TableBody
    :class="cn(
      // Bg base delle celle (necessario per coprire il content sticky sotto)
      '[&_td]:bg-card',
      // Hover: zinc-100 (panel-tracing), visibile sopra il canvas-tracing zinc-50
      '[&_tr:hover_td]:bg-secondary',
      // Selected: petrol-vivid 6% — segnale leggero senza scurire troppo
      '[&_tr[data-state=selected]_td]:bg-accent-vivid/2',
      // Border interno tra le righe
      '[&_tr:not(:last-child)_td]:border-b [&_tr:not(:last-child)_td]:border-border-soft',
      // Smooth color transition
      '[&_td]:transition-colors',
    )"
  >
    <slot />
  </TableBody>
</template>
