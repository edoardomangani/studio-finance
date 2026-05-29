<script setup lang="ts">
import type { AccordionItemProps } from "reka-ui"
import type { HTMLAttributes } from "vue"
import { reactiveOmit } from "@vueuse/core"
import { AccordionItem, useForwardProps } from "reka-ui"
import { cn } from "@/lib/utils"

const props = defineProps<AccordionItemProps & { class?: HTMLAttributes["class"] }>()

const delegatedProps = reactiveOmit(props, "class")

const forwardedProps = useForwardProps(delegatedProps)
</script>

<template>
  <!--
    NOTE: il default shadcn-vue era `border-b last:border-b-0` (pattern
    "lista FAQ con divisori condivisi tra item adiacenti"). Rimosso perché
    incompatibile col nostro uso "accordion come stack di card individuali":
    azzerava il border-bottom dell'ultima card. Lo styling dei bordi vive
    sui consumer ([[InvoicePreviewCard]] e simili futuri) via `props.class`.
  -->
  <AccordionItem
    v-slot="slotProps"
    data-slot="accordion-item"
    v-bind="forwardedProps"
    :class="cn(props.class)"
  >
    <slot v-bind="slotProps" />
  </AccordionItem>
</template>
