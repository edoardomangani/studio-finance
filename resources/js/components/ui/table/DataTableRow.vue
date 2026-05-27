<script setup lang="ts">
/**
 * DataTableRow — riga con auto-checkbox in col 0 e auto-DropdownMenu in
 * ultima col se è presente lo slot #actions.
 *
 * Slot default: i `<TableCell>` intermedi (cells dati).
 * Slot named "actions": i `<DropdownMenuItem>` per il kebab menu.
 *
 *   <DataTableRow :selected="..." @toggle-select="...">
 *     <TableCell>...</TableCell>
 *     <TableCell>...</TableCell>
 *     <template #actions>
 *       <DropdownMenuItem>Modifica</DropdownMenuItem>
 *       <DropdownMenuItem class="text-destructive">Elimina</DropdownMenuItem>
 *     </template>
 *   </DataTableRow>
 *
 * Per disabilitare checkbox: `:selectable="false"`. Senza slot #actions, la
 * cella kebab sparisce.
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
        /** Stato "selezionata" (model). */
        selected?: boolean
        /** Mostra checkbox in col 0. Default true. */
        selectable?: boolean
    }>(),
    { selectable: true },
)

defineEmits<{
    (e: 'toggle-select'): void
}>()
</script>

<template>
  <TableRow :data-state="selected ? 'selected' : undefined">
    <TableCell v-if="selectable">
      <Checkbox
        :model-value="selected"
        @update:model-value="$emit('toggle-select')"
      />
    </TableCell>
    <slot />
    <TableCell v-if="$slots.actions">
      <DropdownMenu>
        <DropdownMenuTrigger as-child>
          <Button
            variant="ghost"
            size="icon-sm"
            aria-label="Azioni"
          >
            <PhDotsThreeVertical />
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
          <slot name="actions" />
        </DropdownMenuContent>
      </DropdownMenu>
    </TableCell>
  </TableRow>
</template>
