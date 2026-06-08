<script setup lang="ts">
/**
 * DataTableHeader — header riga con auto-checkbox in col 0 e auto-cell vuota
 * in ultima col per le actions.
 *
 * Slot default: i `<TableHead>` per le colonne intermedie (tutto tra checkbox
 * e action menu).
 *
 *   <DataTableHeader :all-selected="…" has-actions @toggle-all="…">
 *     <TableHead>Codice</TableHead>
 *     <TableHead>Progetto</TableHead>
 *     ...
 *   </DataTableHeader>
 *
 * Quando `selectable=true` compare la checkbox col 0. Quando `hasActions=true`
 * (default) l'ultima col riserva lo spazio per il kebab menu.
 */
import { Checkbox } from '@/components/ui/checkbox'
import TableHead from './TableHead.vue'
import TableHeader from './TableHeader.vue'
import TableRow from './TableRow.vue'

withDefaults(
    defineProps<{
        /** Stato "tutti selezionati" (model). */
        allSelected?: boolean
        /** Mostra checkbox col 0. Default false. */
        selectable?: boolean
        /** Riserva ultima col per kebab menu (anche se vuoto). Default true. */
        hasActions?: boolean
    }>(),
    { selectable: false, hasActions: true },
)

defineEmits<{
    (e: 'toggle-all'): void
}>()
</script>

<template>
  <TableHeader>
    <TableRow class="border-b-0">
      <TableHead v-if="selectable" class="w-[44px]">
        <Checkbox
          :model-value="allSelected"
          aria-label="Seleziona tutti"
          @update:model-value="$emit('toggle-all')"
        />
      </TableHead>
      <slot />
      <TableHead v-if="hasActions" class="w-[44px]" />
    </TableRow>
  </TableHeader>
</template>
