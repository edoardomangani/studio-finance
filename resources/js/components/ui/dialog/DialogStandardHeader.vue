<script setup lang="ts">
/**
 * DialogStandardHeader — wrapper composto sopra DialogHeader + DialogTitle.
 *
 * Pattern canonico StudioOS:
 *   [code-pill] title (riga 1)
 *   description (riga 2, opzionale)
 *
 * Il `code` è OBBLIGATORIO ed è un identificatore tassonomico della sezione
 * modale (es. M.DEL, M.CRE, M.EDT, M.WIZ), come i codici cartiglio dei form.
 * Non è il codice dell'entità — quello eventualmente vive nel title.
 */
import type { HTMLAttributes } from 'vue'
import { PhX } from '@phosphor-icons/vue'
import DialogClose from './DialogClose.vue'
import DialogDescription from './DialogDescription.vue'
import DialogHeader from './DialogHeader.vue'
import DialogTitle from './DialogTitle.vue'

defineProps<{
  /** Code-pill — identificatore della sezione modale. Obbligatorio. */
  code: string
  /** Titolo principale. Es. "Acme Architettura srl". */
  title: string
  /** Riga descrittiva sotto il title (opzionale). */
  description?: string
  class?: HTMLAttributes['class']
}>()
</script>

<template>
  <DialogHeader :class="$props.class">
    <div class="flex items-center justify-between gap-4">
      <div class="flex min-w-0 items-center gap-2">
        <span class="code-pill">{{ code }}</span>
        <DialogTitle>{{ title }}</DialogTitle>
      </div>
      <div class="flex shrink-0 items-center gap-3">
        <slot v-if="$slots.trailing" name="trailing" />
        <DialogClose
          class="text-muted-foreground hover:text-foreground hover:bg-foreground/3 cursor-pointer rounded-md p-1.25 opacity-80 outline-none transition-colors hover:opacity-100 disabled:pointer-events-none [&_svg]:pointer-events-none [&_svg]:size-3.5 [&_svg]:shrink-0"
        >
          <PhX />
          <span class="sr-only">Close</span>
        </DialogClose>
      </div>
    </div>
    <DialogDescription v-if="description">
      {{ description }}
    </DialogDescription>
  </DialogHeader>
</template>
