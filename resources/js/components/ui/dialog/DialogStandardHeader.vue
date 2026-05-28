<script setup lang="ts">
/**
 * DialogStandardHeader — wrapper composto sopra DialogHeader + DialogTitle.
 *
 * Pattern canonico StudioOS:
 *   title (riga 1, opzionale slot #trailing a destra)
 *   description (riga 2, opzionale)
 *
 * Il close X NON è renderizzato qui — vive su DialogContent
 * (`showCloseButton` default true). Per Dialog senza close (es. ConfirmDialog)
 * passare `:show-close-button="false"` su DialogContent.
 */
import type { HTMLAttributes } from 'vue'
import DialogDescription from './DialogDescription.vue'
import DialogHeader from './DialogHeader.vue'
import DialogTitle from './DialogTitle.vue'

defineProps<{
  /** Titolo principale. Es. "Nuova voce di spesa". */
  title: string
  /** Riga descrittiva sotto il title (opzionale). */
  description?: string
  class?: HTMLAttributes['class']
}>()
</script>

<template>
  <DialogHeader :class="$props.class">
    <div class="flex items-center justify-between gap-4">
      <DialogTitle class="min-w-0 truncate">{{ title }}</DialogTitle>
      <div v-if="$slots.trailing" class="flex shrink-0 items-center gap-3">
        <slot name="trailing" />
      </div>
    </div>
    <DialogDescription v-if="description">
      {{ description }}
    </DialogDescription>
  </DialogHeader>
</template>
