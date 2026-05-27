<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { NumberFieldInput } from "reka-ui"
import { cn } from "@/lib/utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <NumberFieldInput
    data-slot="input"
    :class="cn('flex h-9 w-full rounded-md border border-input bg-card py-1 text-center text-13 transition-colors placeholder:text-muted-foreground/50 outline-none focus-visible:border-input-focus aria-invalid:border-input-invalid disabled:cursor-not-allowed disabled:opacity-50', props.class)"
  />
</template>
