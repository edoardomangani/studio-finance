<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { computed, ref, watch } from 'vue'
import { useFieldContext } from '@/components/ui/field/context'
import { parseDecimal, sanitizeDecimalInput } from '@/lib/format'
import { cn } from '@/lib/utils'

/**
 * Input per importi (€) e percentuali. Rimpiazza `<Input type="number">` per i
 * valori decimali: `type="number"` rifiuta la virgola nei browser con locale
 * non-italiano (il valore diventa invalid e si perde silenziosamente), mentre
 * qui l'utente digita virgola o punto e la virgola diventa subito punto. Lo
 * stato resta sempre nella forma canonica col punto, pronta per il backend
 * (`numeric` / `decimal:0,2`). Clamp su [min, max] e arrotondamento a
 * `decimals` solo on blur (edit grezzo mentre si digita).
 */
const props = withDefaults(
  defineProps<{
    modelValue?: string | number | null
    min?: number
    max?: number
    decimals?: number
    allowNegative?: boolean
    class?: HTMLAttributes['class']
  }>(),
  { decimals: 2 },
)

const emit = defineEmits<{
  (e: 'update:modelValue', value: string): void
  (e: 'input'): void
  (e: 'blur'): void
}>()

const allowNegative = computed(
  () => props.allowNegative ?? (props.min === undefined || props.min < 0),
)

const field = useFieldContext()
const requiredFromField = computed(() => field.required.value || undefined)
const ariaInvalidFromField = computed(() =>
  field.invalid.value ? 'true' : undefined,
)

// `inner` rispecchia il valore del DOM: settandolo PRIMA della patch di Vue,
// la patch su :value diventa un no-op e il caret non salta (vedi onInput).
const inner = ref('')

watch(
  () => props.modelValue,
  (v) => {
    const s = v == null ? '' : String(v)

    if (s !== inner.value) {
      inner.value = s
    }
  },
  { immediate: true },
)

function onInput(event: Event): void {
  const input = event.target as HTMLInputElement
  const raw = input.value
  const caret = input.selectionStart ?? raw.length
  const sanitized = sanitizeDecimalInput(raw, allowNegative.value)

  inner.value = sanitized

  if (input.value !== sanitized) {
    const newCaret = sanitizeDecimalInput(
      raw.slice(0, caret),
      allowNegative.value,
    ).length

    input.value = sanitized
    input.setSelectionRange(newCaret, newCaret)
  }

  emit('update:modelValue', sanitized)
  emit('input')
}

function onBlur(): void {
  let s = inner.value

  if (s !== '') {
    const n = parseDecimal(s, NaN)

    if (!Number.isFinite(n)) {
      s = ''
    } else {
      let clamped = n

      if (props.min !== undefined && clamped < props.min) {
        clamped = props.min
      }

      if (props.max !== undefined && clamped > props.max) {
        clamped = props.max
      }

      const factor = 10 ** props.decimals
      s = String(Math.round(clamped * factor) / factor)
    }
  }

  if (s !== inner.value) {
    inner.value = s
    emit('update:modelValue', s)
  }

  emit('blur')
}
</script>

<template>
  <input
    :value="inner"
    type="text"
    inputmode="decimal"
    :required="requiredFromField"
    :aria-invalid="ariaInvalidFromField"
    data-slot="input"
    :class="
      cn(
        // Classi allineate a Input.vue (variante non-file).
        'file:text-foreground placeholder:text-muted-foreground/50 selection:bg-primary selection:text-primary-foreground dark:bg-input/30 border-input h-9 w-full min-w-0 rounded-md border bg-card px-3 py-2 text-13 transition-colors outline-none disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50',
        'focus-visible:border-input-focus',
        'aria-invalid:border-input-invalid',
        props.class,
      )
    "
    @input="onInput"
    @blur="onBlur"
  >
</template>
