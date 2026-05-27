<script setup lang="ts">
import type { PaginationFirstProps } from "reka-ui"
import type { HTMLAttributes } from "vue"
import { reactiveOmit } from "@vueuse/core"
import { PhCaretDoubleLeft } from "@phosphor-icons/vue"
import { PaginationFirst, useForwardProps } from "reka-ui"
import { cn } from "@/lib/utils"

const props = defineProps<PaginationFirstProps & {
  class?: HTMLAttributes["class"]
}>()

const delegatedProps = reactiveOmit(props, "class")
const forwarded = useForwardProps(delegatedProps)
</script>

<template>
  <PaginationFirst
    data-slot="pagination-first"
    aria-label="Vai alla prima pagina"
    :class="cn(
      'inline-flex h-8 w-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-accent hover:text-foreground disabled:pointer-events-none disabled:opacity-50',
      props.class,
    )"
    v-bind="forwarded"
  >
    <slot>
      <PhCaretDoubleLeft class="size-3.5" />
    </slot>
  </PaginationFirst>
</template>
