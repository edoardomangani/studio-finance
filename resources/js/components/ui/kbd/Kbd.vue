<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <kbd
    :class="cn(
      'bg-muted text-muted-foreground pointer-events-none inline-flex h-5 w-fit min-w-5 items-center justify-center gap-1 rounded-sm px-1 font-sans text-xs font-medium select-none',
      '[&_svg:not([class*=\'size-\'])]:size-3',
      '[[data-slot=tooltip-content]_&]:bg-background/20 [[data-slot=tooltip-content]_&]:text-background dark:[[data-slot=tooltip-content]_&]:bg-background/10',
      // Auto-contrast dentro bottoni con bg scuro (default ink, destructive red): kbd diventa chiaro.
      'in-[.bg-primary]:bg-white/15 in-[.bg-primary]:text-white/85 in-[.bg-primary]:border in-[.bg-primary]:border-white/15',
      'in-[.bg-destructive]:bg-white/15 in-[.bg-destructive]:text-white/85 in-[.bg-destructive]:border in-[.bg-destructive]:border-white/15',
      props.class,
    )"
  >
    <slot />
  </kbd>
</template>
