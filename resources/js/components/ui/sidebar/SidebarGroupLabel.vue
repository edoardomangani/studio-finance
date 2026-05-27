<script setup lang="ts">
import type { PrimitiveProps } from "reka-ui"
import type { HTMLAttributes } from "vue"
import { Primitive } from "reka-ui"
import { cn } from "@/lib/utils"

const props = defineProps<PrimitiveProps & {
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <Primitive
    data-slot="sidebar-group-label"
    data-sidebar="group-label"
    :as="as"
    :as-child="asChild"
    :class="cn(
      'text-sidebar-foreground/70 ring-sidebar-ring relative flex h-6 shrink-0 items-center rounded-md px-2 text-2xs font-medium outline-hidden focus-visible:ring-2 [&>svg]:size-4 [&>svg]:shrink-0',
      // Rail mode: nascondi il testo ma preserva l'altezza per mantenere
      // lo spacing tra gruppi. Aggiungi una hairline centrata come divider.
      'group-data-[collapsible=icon]:text-transparent group-data-[collapsible=icon]:before:absolute group-data-[collapsible=icon]:before:left-1/2 group-data-[collapsible=icon]:before:top-1/2 group-data-[collapsible=icon]:before:h-px group-data-[collapsible=icon]:before:w-5 group-data-[collapsible=icon]:before:-translate-x-1/2 group-data-[collapsible=icon]:before:-translate-y-1/2 group-data-[collapsible=icon]:before:bg-border',
      props.class)"
  >
    <slot />
  </Primitive>
</template>
