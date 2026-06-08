<script setup lang="ts">
/**
 * DataTableBody — sorgente unica per gli stati visivi delle righe dati:
 * hover, active, selected, cursore, divisori. Le righe (DataTableRow)
 * dichiarano lo stato via data-attribute; qui si traduce in colore.
 *
 * Gli stili vivono sul `<td>` (non sul `<tr>`) così la cella copre il
 * contenuto delle colonne sticky che scorre sotto. Il radius + border
 * perimetrale arriva dal container <Table boxed>.
 *
 * Slot default: `<DataTableRow>` ×N (più eventuali righe speciali plain
 * come la riga di espansione, che non essendo `interactive` non prende hover).
 */
import { cn } from '@/lib/utils'
import TableBody from './TableBody.vue'
</script>

<template>
  <TableBody
    :class="cn(
      // Bg base delle celle (copre il content sticky sotto)
      '[&_td]:bg-card [&_td]:transition-colors',
      // Cursore + hover solo sulle righe dichiarate interattive
      '[&_tr[data-interactive]]:cursor-pointer',
      '[&_tr[data-interactive]:hover_td]:bg-muted/40',
      // Selected (checkbox multi-select): whisper petrol-vivid 2%
      '[&_tr[data-state=selected]_td]:bg-accent-vivid/2',
      // Active (dettaglio aperto): fill accent-strong 5% + barra inset 2px
      '[&_tr[data-active]_td]:bg-accent-strong/5',
      '[&_tr[data-active]]:shadow-[inset_2px_0_0_0_var(--accent-strong)]',
      // Divisori interni tra le righe
      '[&_tr:not(:last-child)_td]:border-b [&_tr:not(:last-child)_td]:border-border-soft',
    )"
  >
    <slot />
  </TableBody>
</template>
