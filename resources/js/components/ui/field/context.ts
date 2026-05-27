import type { ComputedRef, InjectionKey } from 'vue'
import { computed, inject } from 'vue'

export type FieldContext = {
  required: ComputedRef<boolean>
  invalid: ComputedRef<boolean>
}

export const FIELD_CONTEXT_KEY: InjectionKey<FieldContext> = Symbol('field-context')

const FALLBACK: FieldContext = {
  required: computed(() => false),
  invalid: computed(() => false),
}

export function useFieldContext(): FieldContext {
  return inject(FIELD_CONTEXT_KEY, FALLBACK)
}
