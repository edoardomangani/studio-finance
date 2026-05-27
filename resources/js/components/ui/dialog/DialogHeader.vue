<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <div
    data-slot="dialog-header"
    :class="cn('flex flex-col border-b border-border-soft px-6 py-5 text-left [&:has(+[data-slot=dialog-footer])]:border-b-0', props.class)"
  >
    <slot />
  </div>
</template>
