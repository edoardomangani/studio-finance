<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <tr
    data-slot="table-row"
    :class="cn('hover:bg-(--row-hover) data-[state=selected]:bg-accent-vivid/2 border-b border-border-soft transition-colors', props.class)"
  >
    <slot />
  </tr>
</template>
