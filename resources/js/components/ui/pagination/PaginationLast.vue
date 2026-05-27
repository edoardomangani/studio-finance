<script setup lang="ts">
import type { PaginationLastProps } from "reka-ui"
import type { HTMLAttributes } from "vue"
import { reactiveOmit } from "@vueuse/core"
import { PhCaretDoubleRight } from "@phosphor-icons/vue"
import { PaginationLast, useForwardProps } from "reka-ui"
import { cn } from "@/lib/utils"

const props = defineProps<PaginationLastProps & {
  class?: HTMLAttributes["class"]
}>()

const delegatedProps = reactiveOmit(props, "class")
const forwarded = useForwardProps(delegatedProps)
</script>

<template>
  <PaginationLast
    data-slot="pagination-last"
    aria-label="Vai all'ultima pagina"
    :class="cn(
      'inline-flex h-8 w-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-accent hover:text-foreground disabled:pointer-events-none disabled:opacity-50',
      props.class,
    )"
    v-bind="forwarded"
  >
    <slot>
      <PhCaretDoubleRight class="size-3.5" />
    </slot>
  </PaginationLast>
</template>
