<script setup lang="ts">
import type { TabsTriggerProps } from 'reka-ui'
import type { HTMLAttributes } from 'vue'
import { reactiveOmit } from '@vueuse/core'
import { TabsTrigger, useForwardProps } from 'reka-ui'
import { cn } from '@/lib/utils'

const props = defineProps<TabsTriggerProps & { class?: HTMLAttributes['class'] }>()
const delegatedProps = reactiveOmit(props, 'class')
const forwarded = useForwardProps(delegatedProps)
</script>

<template>
  <TabsTrigger
    data-slot="tabs-trigger"
    v-bind="forwarded"
    :class="cn(
      'inline-flex items-center justify-center gap-1.5 whitespace-nowrap text-13 font-medium transition-colors cursor-pointer',
      'text-muted-foreground hover:text-foreground data-[state=active]:text-foreground',
      'focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50',
      'h-7 rounded-sm px-3 py-1 data-[state=active]:bg-card data-[state=active]:shadow-xs',
      props.class,
    )"
  >
    <slot />
  </TabsTrigger>
</template>
