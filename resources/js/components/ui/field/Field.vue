<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import type { FieldVariants } from '.'
import { computed, provide, toRefs } from 'vue'
import { cn } from '@/lib/utils'
import { fieldVariants } from '.'
import { FIELD_CONTEXT_KEY } from './context'

const props = defineProps<{
  class?: HTMLAttributes['class']
  orientation?: FieldVariants['orientation']
  required?: boolean
  invalid?: boolean
}>()

const { required, invalid } = toRefs(props)

provide(FIELD_CONTEXT_KEY, {
  required: computed(() => required.value === true),
  invalid: computed(() => invalid.value === true),
})
</script>

<template>
  <div
    role="group"
    data-slot="field"
    :data-orientation="orientation"
    :data-invalid="invalid ? '' : undefined"
    :class="cn(fieldVariants({ orientation }), props.class)"
  >
    <slot />
  </div>
</template>
