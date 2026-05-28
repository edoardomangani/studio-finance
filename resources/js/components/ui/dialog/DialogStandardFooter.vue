<script setup lang="ts">
/**
 * DialogStandardFooter — wrapper composto sopra DialogFooter.
 *
 * Forza il pattern canonico StudioOS:
 *   [ghost Annulla auto-close]  ........  [info opzionale] [slot primary]
 *
 * Drop di tutto il boilerplate di DialogClose + Button "Annulla". Lo slot
 * default è solo per l'azione primaria (Salva, Crea, Avanti, Elimina).
 *
 * Per layout edge (3+ bottoni, custom layout) usa direttamente le primitive.
 */
import type { HTMLAttributes } from 'vue'
import { Button } from '@/components/ui/button'
import { cn } from '@/lib/utils'
import DialogClose from './DialogClose.vue'
import DialogFooter from './DialogFooter.vue'

withDefaults(
  defineProps<{
    /** Label del bottone Annulla. Default "Annulla". */
    cancelLabel?: string
    /** Info al centro/destra (es. "Passo 1 di 6"). */
    info?: string
    class?: HTMLAttributes['class']
  }>(),
  { cancelLabel: 'Annulla' },
)
</script>

<template>
  <DialogFooter :class="cn('sm:justify-between', $props.class)">
    <!-- Annulla nascosto su mobile: la X in alto a destra (DialogContent)
         è già l'affordance convenzionale di cancel/close. -->
    <DialogClose as-child>
      <Button variant="ghost" class="hidden sm:inline-flex">{{ cancelLabel }}</Button>
    </DialogClose>
    <div class="flex items-center gap-3">
      <span
        v-if="info"
        class="font-mono text-2xs tabular text-muted-foreground"
      >{{ info }}</span>
      <slot />
    </div>
  </DialogFooter>
</template>
