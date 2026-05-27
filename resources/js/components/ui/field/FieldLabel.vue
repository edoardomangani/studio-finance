<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { Label } from '@/components/ui/label'
import { cn } from '@/lib/utils'
import { useFieldContext } from './context'

const props = defineProps<{
  class?: HTMLAttributes['class']
}>()

const { required } = useFieldContext()
</script>

<template>
  <Label
    data-slot="field-label"
    :class="cn(
      'group/field-label peer/field-label flex w-fit gap-1 leading-snug group-data-[disabled=true]/field:opacity-50',
      'has-[>[data-slot=field]]:w-full has-[>[data-slot=field]]:flex-col has-[>[data-slot=field]]:rounded-md has-[>[data-slot=field]]:border *:data-[slot=field]:p-4',
      'has-data-[state=checked]:bg-accent-vivid/5 has-data-[state=checked]:border-accent-vivid dark:has-data-[state=checked]:bg-accent-vivid/10',
      'has-[>[data-slot=field]]:**:data-[slot=field-required]:hidden',
      props.class,
    )"
  >
    <slot />
    <span
      v-if="required"
      data-slot="field-required"
      aria-hidden="true"
      class="text-destructive"
    >*</span>
    <span v-if="required" data-slot="field-required" class="sr-only">(richiesto)</span>
  </Label>
</template>
