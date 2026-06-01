<script setup lang="ts">
/**
 * DialogStandardHeader — wrapper composto sopra DialogHeader + DialogTitle.
 *
 * Pattern canonico StudioOS:
 *   riga 1: title + slot #trailing (es. WizardStepper) + close (X)
 *   riga 2: description (opzionale)
 *
 * Il close X vive QUI, allineato al titolo nella stessa riga (non più
 * `absolute` su DialogContent). Per i dialog che forzano una scelta esplicita
 * (ConfirmDialog, delete) passare `:closable="false"`.
 */
import type { HTMLAttributes } from 'vue'
import { PhX } from '@phosphor-icons/vue'
import { DialogClose } from 'reka-ui'
import DialogDescription from './DialogDescription.vue'
import DialogHeader from './DialogHeader.vue'
import DialogTitle from './DialogTitle.vue'

withDefaults(
  defineProps<{
    /** Titolo principale. Es. "Nuova voce di spesa". */
    title: string
    /** Riga descrittiva sotto il title (opzionale). */
    description?: string
    /**
     * Mostra il bottone di chiusura (X) nella riga del titolo. Default true.
     * Passare `false` per i dialog che forzano una scelta esplicita (conferme,
     * delete): la X come terza via d'uscita indebolisce la decisione.
     */
    closable?: boolean
    class?: HTMLAttributes['class']
  }>(),
  { closable: true },
)
</script>

<template>
  <DialogHeader :class="$props.class">
    <div class="flex items-center justify-between gap-4">
      <DialogTitle class="min-w-0 truncate">{{ title }}</DialogTitle>
      <div v-if="$slots.trailing || closable" class="flex shrink-0 items-center gap-3">
        <slot name="trailing" />
        <DialogClose
          v-if="closable"
          data-slot="dialog-close"
          class="text-muted-foreground hover:text-foreground -mr-1 rounded-md p-1.25 opacity-80 transition-colors outline-none hover:bg-foreground/3 hover:opacity-100 focus-visible:text-foreground focus-visible:opacity-100 focus-visible:ring-1 focus-visible:ring-accent-line disabled:pointer-events-none [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*='size-'])]:size-3.5 cursor-pointer"
        >
          <PhX />
          <span class="sr-only">Chiudi</span>
        </DialogClose>
      </div>
    </div>
    <DialogDescription v-if="description">
      {{ description }}
    </DialogDescription>
  </DialogHeader>
</template>
