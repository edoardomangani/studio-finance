<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"

const props = defineProps<{ class?: HTMLAttributes["class"] }>()
</script>

<template>
  <div
    data-slot="dialog-footer"
    :class="cn('flex flex-col-reverse gap-2 border-t border-border-soft bg-muted/50 px-6 py-4 sm:flex-row sm:items-center sm:justify-end', props.class)"
  >
    <slot />
  </div>
</template>
