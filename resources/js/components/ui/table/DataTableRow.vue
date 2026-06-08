<script setup lang="ts">
/**
 * DataTableRow — riga dati canonica. Espone gli stati come data-attribute;
 * il look (hover, active, selected, cursore) lo applica DataTableBody.
 *
 * - `interactive`: la riga apre un dettaglio al click → cursore + hover.
 *   Lega `@click` direttamente sulla riga.
 * - `active`: la riga il cui dettaglio/sheet è aperto (accent-strong + barra).
 * - `selected`: multi-select via checkbox in col 0 (`selectable`), whisper.
 *
 * Slot default: i `<TableCell>` dati. Slot `actions`: i `<DropdownMenuItem>`
 * del kebab; la cella si genera da sé e ferma la propagazione del click.
 *
 *   <DataTableRow interactive :active="isOpen" @click="open(row)">
 *     <TableCell>…</TableCell>
 *     <template #actions>
 *       <DropdownMenuItem>Modifica</DropdownMenuItem>
 *       <DropdownMenuItem variant="destructive">Archivia</DropdownMenuItem>
 *     </template>
 *   </DataTableRow>
 */
import { PhDotsThreeVertical } from '@phosphor-icons/vue'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import TableCell from './TableCell.vue'
import TableRow from './TableRow.vue'

withDefaults(
    defineProps<{
        /** Riga cliccabile che apre un dettaglio: cursore + hover. */
        interactive?: boolean
        /** Riga il cui dettaglio è aperto: bg accent-strong + barra inset. */
        active?: boolean
        /** Stato multi-select (richiede `selectable`). */
        selected?: boolean
        /** Mostra checkbox in col 0. Default false. */
        selectable?: boolean
        /** Aria-label della checkbox. */
        selectLabel?: string
    }>(),
    { selectable: false },
)

defineEmits<{
    (e: 'toggle-select'): void
}>()
</script>

<template>
  <TableRow
    :data-interactive="interactive || undefined"
    :data-active="active || undefined"
    :data-state="selected ? 'selected' : undefined"
  >
    <TableCell v-if="selectable" @click.stop>
      <Checkbox
        :model-value="selected"
        :aria-label="selectLabel"
        @update:model-value="$emit('toggle-select')"
      />
    </TableCell>
    <slot />
    <TableCell v-if="$slots.actions" class="text-right" @click.stop>
      <DropdownMenu>
        <DropdownMenuTrigger as-child>
          <Button
            type="button"
            variant="ghost"
            size="icon-sm"
            aria-label="Azioni"
          >
            <PhDotsThreeVertical :size="14" weight="bold" />
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
          <slot name="actions" />
        </DropdownMenuContent>
      </DropdownMenu>
    </TableCell>
  </TableRow>
</template>
