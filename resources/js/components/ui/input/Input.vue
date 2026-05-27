<script setup lang="ts">
import type { HTMLAttributes, InputHTMLAttributes } from 'vue'
import { useVModel } from '@vueuse/core'
import { computed, ref } from 'vue'
import { useFieldContext } from '@/components/ui/field/context'
import { cn } from '@/lib/utils'

const props = defineProps<{
  defaultValue?: string | number
  modelValue?: string | number
  class?: HTMLAttributes['class']
  type?: InputHTMLAttributes['type']
}>()

const emits = defineEmits<{
  (e: 'update:modelValue', payload: string | number): void
}>()

const isFile = computed(() => props.type === 'file')

const modelValue = useVModel(props, 'modelValue', emits, {
  passive: true,
  defaultValue: props.defaultValue,
})

const field = useFieldContext()
const requiredFromField = computed(() => field.required.value || undefined)
const ariaInvalidFromField = computed(() =>
  field.invalid.value ? 'true' : undefined,
)

const el = ref<HTMLInputElement | null>(null)

defineExpose({ el })
</script>

<template>
  <input
    v-if="isFile"
    ref="el"
    type="file"
    :required="requiredFromField"
    :aria-invalid="ariaInvalidFromField"
    data-slot="input"
    :class="cn(
      'border-input flex h-9 w-full cursor-pointer items-center rounded-md border bg-card px-3 py-2 text-13 transition-colors outline-none',
      'file:mr-3 file:rounded-md file:border file:border-border file:bg-card file:px-3 file:py-1 file:text-13 file:font-medium file:text-foreground hover:file:bg-muted',
      'focus-visible:border-input-focus',
      'aria-invalid:border-input-invalid',
      'disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50',
      props.class,
    )"
  >
  <input
    v-else
    ref="el"
    v-model="modelValue"
    :type="type"
    :required="requiredFromField"
    :aria-invalid="ariaInvalidFromField"
    data-slot="input"
    :class="cn(
      'file:text-foreground placeholder:text-muted-foreground/50 selection:bg-primary selection:text-primary-foreground dark:bg-input/30 border-input h-9 w-full min-w-0 rounded-md border bg-card px-3 py-2 text-13 transition-colors outline-none disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50',
      'focus-visible:border-input-focus',
      'aria-invalid:border-input-invalid',
      props.class,
    )"
  >
</template>
