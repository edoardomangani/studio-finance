<script setup lang="ts">
import type { TagsInputItemProps } from "reka-ui"
import type { HTMLAttributes } from "vue"

import { reactiveOmit } from "@vueuse/core"
import { TagsInputItem, useForwardProps } from "reka-ui"
import { cn } from "@/lib/utils"

const props = defineProps<TagsInputItemProps & { class?: HTMLAttributes["class"] }>()

const delegatedProps = reactiveOmit(props, "class")

const forwardedProps = useForwardProps(delegatedProps)
</script>

<template>
  <TagsInputItem v-bind="forwardedProps" :class="cn('inline-flex items-center gap-1 rounded-full bg-accent-vivid/10 text-accent-strong pl-2 pr-1 py-0.5 text-xs font-medium border border-transparent data-[state=active]:border-accent-vivid transition-colors', props.class)">
    <slot />
  </TagsInputItem>
</template>
