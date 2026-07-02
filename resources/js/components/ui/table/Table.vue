<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
  /** Classe sul container esterno (la scatola boxed): es. hide responsive. */
  containerClass?: HTMLAttributes["class"]
  /**
   * Boxed (default per DataTable): wrap in card con border + radius.
   * Header dentro al box (`bg-muted/50`), body con divisori tra righe.
   * Lo scroll orizzontale (`overflow-x-auto`) vive dentro al box per tabelle
   * con `min-w-[…]` superiore alla larghezza del container.
   *
   * Niente scroll verticale interno — le righe crescono naturalmente. Se in
   * futuro serve uno scroll-y dentro al box (con sticky header), si aggiunge
   * qui come variant separata.
   */
  boxed?: boolean
}>()
</script>

<template>
  <div
    v-if="boxed"
    data-slot="table-container"
    :class="cn('overflow-x-auto rounded-md border border-border bg-card', props.containerClass)"
  >
    <table
      data-slot="table"
      :class="cn(
        'w-full caption-bottom text-13',
        '[&>thead]:bg-muted/50 [&>thead]:border-b [&>thead]:border-border',
        props.class,
      )"
    >
      <slot />
    </table>
  </div>
  <div
    v-else
    data-slot="table-container"
    :class="cn('relative w-full overflow-x-auto', props.containerClass)"
  >
    <table data-slot="table" :class="cn('w-full caption-bottom text-13', props.class)">
      <slot />
    </table>
  </div>
</template>
